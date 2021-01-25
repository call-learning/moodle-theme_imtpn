<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Setup routine for theme
 *
 * @package   theme_imtpn
 * @copyright 2021 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_imtpn;

use block_base;
use context_block;
use context_course;
use context_system;
use file_exception;
use moodle_page;
use moodle_url;
use stored_file_creation_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class setup
 *
 * Utility setup class.
 *
 * @copyright   2020 Laurent David - CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup {

    /**
     * Install updates
     */
    public static function install_update() {
        global $PAGE, $CFG, $DB;

        static::setup_config_values();

        require_once($CFG->dirroot . '/my/lib.php');
        // Get the default Dashboard block.
        $defaultmy = my_get_page(null, MY_PAGE_PRIVATE);

        $regionname = 'content';
        $page = new moodle_page();
        $page->set_pagetype('my-index');
        $page->set_subpage($defaultmy->id);
        $page->set_url(new moodle_url('/'));
        $page->set_context(context_system::instance());

        $oldpage = $PAGE;
        $PAGE = $page;
        static::setup_page_blocks($page, self::DASHBOARD_BLOCK_DEFINITION);
        my_reset_page_for_all_users();
        // Note here: this will only define capabilities for the default page. If we
        // want the dashboard to work as expected we also need to set forcedefaultmymoodle to true.

        // Setup Home page
        $page = new moodle_page();
        $page->set_pagetype('site-index');
        $page->set_docs_path('');

        $PAGE = $page;
        static::setup_page_blocks($page, self::HOMEPAGE_BLOCK_DEFINITION);
        $PAGE = $oldpage;
    }

    /**
     * Setup dashboard  - to be completed
     *
     * @return bool
     * @throws \dml_exception
     */
    public static function setup_page_blocks($page, $blockdeflist, $regionname = 'content') {
        global $DB;
        $transaction = $DB->start_delegated_transaction(); // Do not commit transactions until the end.
        $blocks = $page->blocks;
        $blocks->add_regions(array($regionname), false);
        $blocks->set_default_region($regionname);
        $blocks->load_blocks();

        // Delete unceessary blocks.
        $centralblocks = $blocks->get_blocks_for_region($regionname);
        foreach ($centralblocks as $cb) {
            blocks_delete_instance($cb->instance);
        }
        // Add the blocks.
        foreach ($blockdeflist as $blockdef) {
            global $DB;
            $blockinstance = (object) $blockdef;
            $blockinstance->parentcontextid = $page->context->id;
            $blockinstance->pagetypepattern = $page->pagetype;
            $blockinstance->subpagepattern = $page->subpage;
            if (!empty($blockinstance->configdata)) {
                $blockinstance->configdata = base64_encode(serialize((object) $blockinstance->configdata));

            } else {
                $blockinstance->configdata = '';
            }
            $blockinstance->timecreated = time();
            $blockinstance->timemodified = $blockinstance->timecreated;

            $contextdefs = [];
            if (!empty($blockinstance->capabilities)) {
                $contextdefs = $blockinstance->capabilities;
                unset($blockinstance->capabilities);
            }

            $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);
            if (!empty($blockdef['files'])) {
                static::upload_files_in_block($blockinstance, $blockdef['files']);
            }
            // Ensure the block context is created.
            context_block::instance($blockinstance->id);

            // If the new instance was created, allow it to do additional setup.
            if ($block = block_instance($blockinstance->blockname, $blockinstance)) {
                $block->instance_create();
            }
            foreach ($contextdefs as $capability => $roles) {
                foreach ($roles as $rolename => $permission) {
                    $roleid = $DB->get_field('role', 'id', array('shortname' => $rolename));
                    if ($roleid) {
                        role_change_permission($roleid, $block->context, $capability, $permission);
                    }
                }
            }
        }
        $DB->commit_delegated_transaction($transaction);// Ok, we can commit.
        return true;
    }

    /**
     * @param object $blockinstance
     * @param $files
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    protected static function upload_files_in_block($blockinstance, $files) {
        global $DB;
        $configdata = unserialize(base64_decode($blockinstance->configdata));
        $context = context_block::instance($blockinstance->id);
        foreach ($files as $filename => $filespec) {
            $filerecord = array(
                'contextid' => $context->id,
                'component' => 'block_' . $blockinstance->blockname,
                'filearea' => empty($filespec['filearea']) ? "files" : $filespec['filearea'],
                'itemid' => $blockinstance->id,
                'filepath' =>dirname($filename) == '.'? '/': dirname($filename),
                'filename' => basename($filename),
            );
            // Create an area to upload the file.
            $fs = get_file_storage();
            // Create a file from the string that we made earlier.
            if (!($file = $fs->get_file($filerecord['contextid'],
                $filerecord['component'],
                $filerecord['filearea'],
                $filerecord['itemid'],
                $filerecord['filepath'],
                $filerecord['filename']))) {
                global $CFG;
                $originalpath = $CFG->dirroot;
                $originalpath .= empty($filespec['filepath']) ?
                    "/theme/imtpn/data/files/{$filerecord['filename']}" : $filespec['filepath'];

                $file = $fs->create_file_from_pathname($filerecord,
                    $originalpath);
            }
            if (!empty($filespec['textfields'])) {
                foreach ($filespec['textfields'] as $textfield) {
                    $configdata->{$textfield} =
                        file_rewrite_pluginfile_urls($configdata->{$textfield},
                            'pluginfile.php',
                            $context->id,
                            'block',
                            $filerecord['filearea'],
                            $filerecord['itemid']
                        );
                }
            }
        }
        $DB->update_record('block_instances',
            [
                'id' => $blockinstance->id,
                'configdata' => base64_encode(serialize($configdata)),
                'timemodified' => time()
            ]);
    }


    // @codingStandardsIgnoreStart
    // phpcs:disable
    /**
     * Dashboard block definition
     */
    const DASHBOARD_BLOCK_DEFINITION = array(
        array(
            'blockname' => 'html',
            'showinsubcontexts' => '1',
            'defaultregion' => 'content',
            'defaultweight' => '0',
            'configdata' =>
                [
                    "title" => "",
                    "format" => "1",
                    "classes" => "",
                    "backgroundcolor" => "",
                    "text" => "<h4>Bienvenue !</h4>
<p>Que voulez-vous faire aujourd’hui ?</p>
<p><br>
</p>
<table class='db-action-block'>
    <tbody>
        <tr>
            <td scope='col'>
                <div><i class='fa fa-book'></i></div>
                <div>Créer un nouveau cours</div>
            </td>
            <td scope='col'>
                <div><i class='fa fa-search'></i></div>
                <div>Partager une ressource</div>
            </td>
            <td scope='col'>
                <div><i class='fa fa-'></i></div>
                <div>Explorer le catalogue</div>
            </td>
            <td scope='col'>
                <div><i class='fa fa-wechat'></i></div>
                <div>Echanger avec mes collègues</div>
            </td>
        </tr>
    </tbody>
</table>
<br><br>
<p></p>"],
            'capabilities' => array()
        ),
    );
    /**
     * Homepage block definition
     */
    const HOMEPAGE_BLOCK_DEFINITION = array(
        array(
            'blockname' => 'html',
            'showinsubcontexts' => '1',
            'defaultregion' => 'content',
            'defaultweight' => '0',
            'configdata' =>
                [
                    "title" => "Qu’est-ce que la Pédagothèque Numérique ?",
                    "format" => "1",
                    "classes" => "",
                    "text" => "<p>La Pédagothèque Numérique est une plateforme permettant de regrouper tout le contenu pédagogique 
            des écoles du groupe Institut Mines-Télécom. 
            Elle a pour vocation de favoriser les échanges entre écoles et d’harmoniser les enseignements. Elle met à disposition des cours en accès libre afin que tout les membres de l’IMT, étudiants comme enseignants ou membres de l’équipe adminstrative puisse bénéficier du savoir détenu dans toutes les écoles..</p>"],
            'capabilities' => array()
        ),
        array('blockname' => 'mcms',
            'showinsubcontexts' => '1',
            'defaultregion' => 'content',
            'defaultweight' => '1',
            'configdata' => [
                "title" => "Pour les enseignants",
                "format" => "1",
                "classes" => "",
                "backgroundcolor" => "",
                "text" => "<p>Parcourez les ressources mises à disposition par vos homologues des autres écoles du groupe, construisez vos cours grâce à ce contenu partagé et mettez en ligne vos prochains cours.</p>
            <p>Échangez sur votre spécialité ou vos thèmes d’affections avec vos collègues grâce au mur pédagogique.</p>
            <p>Vous pouvez également prendre le temps de suivre des cours dédiés au personnel de l’IMT ou des cours dispensés par vos collègues pour élargir vos connaissances.</p>
            <strong>Qu’est-ce que vous voulez faire ?</strong>
            <ul>
            <li><a href='#'>Transformer mes enseignements à distance <i></i></a></li>
            <li><a href='#'>Échanger entre enseignants </a></li>
            <li><a href='#'>Créer un dispositif de formation en ligne </a></li>
            <li><a href='#'>M’inspirer et partager des pratiques innovantes </a></li>
            <li><a href='#'>M’inspirer et partager des pratiques innovantes </a></li>
            <li><a href='#'>Valoriser et faire reconnaitre mon parcours d’enseignement </a></li>
            </ul>
            ",
                "layout" => "layout_two"
            ],
            'capabilities' => array(
                'moodle/block:view' => ['jury' => CAP_PROHIBIT]
            ),
            'files' => [
                'side-image.png' => [
                    'filepath' => '/theme/imtpn/data/files/fp/teacher.png',
                    'filearea' => 'images'
                ]
            ],
        ),

        array('blockname' => 'featured_courses',
            'showinsubcontexts' => '1',
            'defaultregion' => 'content',
            'defaultweight' => '2',
            'configdata' => [
                "title" => "Cours à la une",
                "selectedcourses" => []
            ],
            'capabilities' => array()
        ),
    );

    // phpcs:enable
    // @codingStandardsIgnoreEnd

    /**
     * Setup config values
     */
    public static function setup_config_values() {
        foreach (self::DEFAULT_SETTINGS as $pluginname => $plugindefs) {
            $plugin = $pluginname;
            if ($pluginname === 'moodle') {
                $plugin = null;
            }
            foreach ($plugindefs as $key => $value) {
                $configvalue = get_config($plugin, $key);
                if ($configvalue != $value) {
                    set_config($key, $value, $plugin);
                }
            }
        }
    }

    /**
     * The defaults settings
     */
    const DEFAULT_SETTINGS = [
        'moodle' => [
            'country' => 'FR',
            'timezone' => 'Europe/Paris',
        ],
        'block_html' => [
            'allowcssclasses' => true
        ],
    ];
}