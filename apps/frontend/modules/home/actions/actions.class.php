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

		$epg = new EPG();
        $this->channels = $epg->currentPlayingChannels();
	}

	public function executePost(sfWebRequest $request)
	{
		sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

		$cF = new CylonFacebook();
        $userInfo = $cF->getUserInfo();

        $boxMapping = new BoxMapping();
        $boxIP = $boxMapping->getBoxIp($cF->getUserId());

        $stb = new STB($boxIP);
        $channelId = $stb->getCurrentChannelId();

        $epg = new EPG();
        $channels = $epg->currentPlayingChannels();

        $programme = $channels[$channelId]['now_playing'];
        $channel = $channels[$channelId]['name'];

		$cF->postToWall($channel, $programme, url_for('@switch_channel?channelId='.$channelId, true));

		return $this->redirect('home/index');
	}

	public function executeBuy(sfWebRequest $request)
	{

	}
}
