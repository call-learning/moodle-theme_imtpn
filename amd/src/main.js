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
export const init = () => {
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll('#language-trigger-selection').onclick(() => {
                const item = document.querySelector('#language-list');
                item.classList.toggle('-active');
            }
        );
        document.querySelectorAll('#mask-language-choice, .language-list .language-choice').onclick(() => {
                const item = document.querySelector('#language-list');
                item.classList.remove('-active');
            }
        );
    });
};