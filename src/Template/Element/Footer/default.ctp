<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
use Cake\Core\Configure;
$version = Configure::read('passbolt.version');
$privacyPolicyUrl = Configure::read('passbolt.legal.privacy_policy.url');
$isTrial = $isTrial ?? false;
?>
<footer>
    <div class="footer">
        <ul class="footer-links">
<?php if ($isTrial && !$isExpired) : ?>
            <li class="warning message">Free trial</li>
<?php endif; ?>
<?php if ($isTrial && $isExpired) : ?>
            <li class="error message">Expired trial</li>
<?php endif; ?>
            <li><a href="https://www.passbolt.com/terms" title="terms of service"><?= __('Terms'); ?></a></li>
<?php if (!empty($privacyPolicyUrl)) : ?>
            <li><a href="<?php echo $privacyPolicyUrl ?>"><?= __('Privacy'); ?></a></li>
<?php endif; ?>
            <li><a href="https://www.passbolt.com/credits"><?= __('Credits'); ?></a></li>
            <li id="version">
                <a href="https://www.passbolt.com/credits" class="tooltip-left" data-tooltip="<?= $version; ?>">
                    <i class="fa fa-heart-o"></i>
                    <span class="visuallyhidden"><?= __('Versions'); ?></span>
                </a>
            </li>
        </ul>
    </div>
</footer>
