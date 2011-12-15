<?php
require(dirname(__FILE__).'/vendor/facebook/facebook.php');

class CylonFacebook {
	private $facebook = null;

	const PERMISSIONS = 'email,publish_stream,user_birthday,user_about_me,user_hometown';
	const NAME = 'Cyclon BSKyB APP';
	const DESCRIPTION = 'Sky channel checking-in!';

	public function __construct()
	{
		$config = array(
			'appId' => '209294285820421',
			'secret' => '9c37b078229303d4e50d50579b7387bc',
			'fileUpload' => false
		);
		$this->facebook = new Facebook($config);
	}

	/**
	 * @static
	 * @return Facebook
	 */
	public function getFacebook()
	{
		return $this->facebook;
	}

	public function getUserId()
	{
		return $this->facebook->getUser();
	}

	public function getUserInfo()
	{
		return $this->facebook->api('/'.$this->getUserId());
	}

	public function postToWall($message, $link)
	{
		return $this->facebook->api('/'.$this->getUserId().'/feed', 'post', array(
			'message' => $message,
			'link' => $link,
			'name' => self::NAME,
			'description' => self::DESCRIPTION
		));
	}
}
