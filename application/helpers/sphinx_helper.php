<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('sphinx_init_helper')) {
	function sphinx_init_helper($config)
	{
		if (!extension_loaded('sphinx')) {
			$CI = &get_instance();
			$CI->load->library('SphinxClient');
		}
		$sphinxClient = new SphinxClient();
		$sphinxClient->SetServer($config['host'], $config['port']);
		$sphinxClient->SetConnectTimeout($config['timeout']);
		$sphinxClient->SetArrayResult(true);
		$sphinxClient->SetMatchMode(SPH_MATCH_EXTENDED2);

		return $sphinxClient;
	}
}
