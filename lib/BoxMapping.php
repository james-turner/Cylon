<?php

class BoxMapping
{
	private $map = array();

	public function __construct()
	{
		$fileMap = json_decode(file_get_contents(sfConfig::get('sf_data_dir').'/userBoxMapping.json'), true);
		foreach($fileMap AS $mapping) {
			$this->map[$mapping['userId']] = $mapping['boxIP'];
		}
	}

	public function getBoxIp($userId)
	{
		return array_key_exists($userId, $this->map) ? $this->map[$userId] : null;
	}
}
