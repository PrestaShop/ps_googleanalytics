/*
* File: /upgrade/Upgrade-2.0.3.php
*/
<?php
if (!defined('_PS_VERSION_'))
    exit;
 
function upgrade_module_2_0_3($object)
{
    return ($object->registerHook('adminOrder')
      && $object->registerHook('footer')
      && $object->registerHook('home')
      && $object->registerHook('backOfficeHeader')
      && $object->registerHook('productfooter'));
}
