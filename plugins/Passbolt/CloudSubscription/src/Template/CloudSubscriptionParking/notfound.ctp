<?php
use Cake\Core\Configure;
$title = __('This is not the workspace you are looking for.');
$this->assign('title',	$title);
$this->assign('pageClass', 'cloud cloud-archived');
$this->Html->css('themes/default/api_cloud.min.css?v=' . Configure::read('passbolt.version'), ['block' => 'css', 'fullBase' => true]);
?>
<!-- header -->
<header>
    <div class="header first ">
        <nav class="">
            <div class="top navigation primary">
                <ul class="links clearfix">
                    <li><a href="https://www.passbolt.com"><span>home</span></a></li>
                    <li><a href="http://community.passbolt.com/"><span>forum</span></a></li>
                    <li><a href="https://help.passbolt.com"><span>help</span></a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>
<div class="grid grid-responsive-12">
    <div class="row">
        <div class="col4 push2 last">
            <div class="logo no-img">
                <h1><span>Passbolt</span></h1>
            </div>
            <h2>
                <?= $title; ?>
            </h2>
            <p>
                <?= __('This workspace does not exist or has been deleted due to inactivity.'); ?>
                <?= __('Check the name of the organization in the url or get in touch with support if you think this is a mistake.'); ?>
            </p>
            <a class="button primary" href="https://www.passbolt.com/contact/cloud-help" class="secondary-cta">
                <?= __('contact support'); ?>
            </a>
        </div>
    </div>
</div>
