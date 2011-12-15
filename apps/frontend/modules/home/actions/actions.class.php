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
		$this->boxIP = $boxMapping->getBoxIp($cF->getUserId());

		if ($this->boxIP) {
			$stb = new STB($this->boxIP);

			$this->channelId = $stb->getCurrentChannelId();

			$epg = new EPG();
			$this->channels = $epg->currentPlayingChannels();
		}
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
        $img = isset($channels[$channelId]['img'])
            ? 'http://epgstatic.sky.com/epgdata/1.0/paimage/6/0/' . $channels[$channelId]['img']
            : null;

        $cF->postToWall($channel, $programme, url_for('@switch_channel?channelId='.$channelId, true), $img);

		$this->redirect('home/index');
	}

	public function executeBuy(sfWebRequest $request)
	{

	}
}
