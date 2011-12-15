<?php

class homeActions extends sfActions
{
	public function executeIndex(sfWebRequest $request)
	{
		$cF = new CylonFacebook();
		$fb = $cF->getFacebook();
		sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
		$this->logoutUrl = $fb->getLogoutUrl(array(
			'next' => url_for('user/login', true)
		));


		// sample data from FB
		$userInfo = $cF->getUserInfo();

		$boxMapping = new BoxMapping();
        $boxIP = $boxMapping->getBoxIp($cF->getUserId());

        $stb = new STB($boxIP);

        $this->boxIP = $boxIP;
        $this->channelId = $stb->getCurrentChannelId();
	}

	public function executePost(sfWebRequest $request)
	{
		sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

		$cF = new CylonFacebook();
		$cF->postToWall('Random message '.rand(), url_for('home/index', true));

		$this->redirect('home/index');
	}
}
