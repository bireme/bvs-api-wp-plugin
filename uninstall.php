<?php
// Se desejar remover opções ao desinstalar:
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('bv_api_endpoint');
delete_option('bv_custom_css');
delete_option('bv_custom_js');
