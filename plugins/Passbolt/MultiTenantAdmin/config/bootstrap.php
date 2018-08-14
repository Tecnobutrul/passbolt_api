<?php
use Cake\Core\Configure;

// Load default plugin configuration file while keeping plugin conf defined in passbolt.php.
$original = Configure::read('passbolt.multiTenantAdmin');
Configure::load('Passbolt/MultiTenantAdmin.config', 'default', true);
$defaults = Configure::read('passbolt.multiTenantAdmin');
$merged = array_merge($defaults, $original);
Configure::write('passbolt.multiTenantAdmin', $merged);

