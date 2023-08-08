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
 * A javascript module
 *
 */

import $ from 'jquery';
import config from 'core/config';
import {addIconToContainerWithPromise} from 'core/loadingicon';

export const init = () => {
    document.addEventListener('resource_library_card_rendered', () => {
        const addToBasketCB = (event) => {
            if ($) {
                // Create a spinning icon on the card.
                const iconPromise = addIconToContainerWithPromise(event.currentTarget);
                const courseid = document.querySelector('.local-resourcelibrary.block-cards').dataset.parentId;
                $.post(config.wwwroot + '/blocks/sharing_cart/rest.php', {
                    "action": "backup",
                    "cmid": event.target.parentElement.dataset.moduleId,
                    "userdata": false,
                    "sesskey": config.sesskey,
                    "courseid": courseid
                }).then(() => {
                    $.post(config.wwwroot + '/blocks/sharing_cart/rest.php',
                        {
                            "action": "render_tree",
                            "courseid": courseid
                        },
                        function (response) {
                            $('.block_sharing_cart .tree').replaceWith($(response));
                            $.init_item_tree();
                        }, "html").catch((error) => window.console.log(error));
                    iconPromise.resolve();
                });
            }
        };
        document.querySelectorAll('.resource-library-activities .sharing-cart-icon').forEach(
            (element) => {
                element.onclick = addToBasketCB;
            }
        );
    });
};
