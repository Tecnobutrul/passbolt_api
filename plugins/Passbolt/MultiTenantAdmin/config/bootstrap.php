<?php
/**
 * Passbolt Cloud
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @link          https://www.passbolt.com Passbolt(tm)
 */
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Passbolt\MultiTenant\Event\EmailQueueInitializeListener;

Configure::load('Passbolt/MultiTenantAdmin.config', 'default', true);

// Override the email_queue database datasource on initialize the email_queue table.
$listener = new EmailQueueInitializeListener();
EventManager::instance()->on($listener);
