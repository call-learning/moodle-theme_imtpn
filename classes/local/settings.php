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
 * Theme settings. In one place.
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_imtpn\local;

use admin_settingpage;

defined('MOODLE_INTERNAL') || die;

/**
 * Theme settings. In one place.
 *
 * @package   theme_imtpn
 * @copyright 2020 - CALL Learning - Laurent David <laurent@call-learning>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings extends \theme_clboost\local\settings {

    /**
     * Additional settings
     *
     * This is intended to be overriden in the subtheme to add new pages for example.
     *
     * @param admin_settingpage $settings
     */
    protected static function additional_settings(admin_settingpage &$settings) {
        // Advanced settings.
        $page = new admin_settingpage('footer',
            static::get_string('footer', 'theme_imtpn'));

        $setting = new \admin_setting_confightmleditor('theme_imtpn/footercontent',
            static::get_string('footercontent', 'theme_imtpn'),
            static::get_string('footercontent_desc', 'theme_imtpn'),
            self::DEFAULT_FOOTER_CONTENT,
            PARAM_RAW);
        $page->add($setting);

        $settings->add($page);

        // Advanced settings.
        $page = new admin_settingpage('murpedago',
            static::get_string('murpedagogique', 'theme_imtpn'));

        $setting = new \admin_setting_configtext('theme_imtpn/murpedagoidnumber',
            static::get_string('murpedagoidnumber', 'theme_imtpn'),
            static::get_string('murpedagoidnumber_desc', 'theme_imtpn'),
            'MUR_PEDAGOGIQUE',
            PARAM_ALPHANUMEXT);
        $page->add($setting);

        $setting = new \admin_setting_confightmleditor('theme_imtpn/murpedagogrouprules',
            static::get_string('murpedagogrouprules', 'theme_imtpn'),
            static::get_string('murpedagogrouprules_desc', 'theme_imtpn'),
            self::DEFAULT_RULES,
            PARAM_RAW);
        $page->add($setting);

        $settings->add($page);

    }

    const DEFAULT_FOOTER_CONTENT='
    <div class="footer__stores">
        <a href="#">
            <img src="/theme/imtpn/pix/logos/logo-appstore.png" alt="disponible sur app store">
          </a>
        <a href="#">
            <img src="/theme/imtpn/pix/logos/logo-googleplay.png" alt="disponible sur google play">
        </a>
    </div>
    ';
    const DEFAULT_RULES = "
        <h3>Règles de participation</h3>
        <p><strong>Respect</strong> : les utilisateurs doivent s’adresser aux autres utilisateurs, à la modération et à 
        l'administration du Mur pédagogique avec respect, en évitant les commentaires irritants, irrespectueux, faux ou pouvant 
        porter préjudice à un utilisateur ou une entité.</p>
        <p><strong>Spams</strong> : tout message doit rester en lien avec la thématique du groupe et sans intention 
        promotionnelle ou commerciale.
        <p><strong>Responsabilité des auteurs</strong>: les auteurs de messages sont seuls responsables de leur propos 
        et des contenus qu’ils y joignent. Ceux-ci ne sont, par ailleurs, pas nécessairement approuvés par 
        l’administration de la Pédagothèque numérique ou la Direction de l’IMT.</p>
        <p><strong>Visibilité des messages</strong> : les messages ne sont visibles que par les utilisateurs 
        authentifiés sur la Pédagothèque numérique, membres du groupe concerné.L’équipe de modération se réserve le 
        droit de supprimer, avec ou sans avertissement, à sa discrétion, tout message qui ne respectent pas ces 
        règles. Si, en tant qu'utilisateur, vous jugez qu'une contribution ne respecte pas ces règles, merci de le signaler 
        à l’adresse pedagotheque@imt.fr.</p>
        <p>Il est néanmoins possible que nous commettions des erreurs d’interprétation : si vous pensez qu’une contribution 
        a été supprimée par erreur, merci de le signaler à l’adresse pedagotheque@imt.fr.</p>
    ";
}