<?php

class stbActions extends sfActions
{

	public function executeSwitch(sfWebRequest $request)
	{

        sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

		$this->channelId = $request->getParameter('channelId', null);

		$cF = new CylonFacebook();
        $userInfo = $cF->getUserInfo();



		$boxMapping = new BoxMapping();
		$boxIP = $boxMapping->getBoxIp($cF->getUserId());

		if (null === $boxIP) {
			return $this->redirect('home/buy');
		}

		$stb = new STB($boxIP);
		$stb->ChannelChange($this->channelId);
//
//        $epg = new EPG();
//        $channels = $epg->currentPlayingChannels();
//        $info = $channels[$this->channelId];
//
//        $cF->postToWall(sprintf('%s is now watching "%s" on "%s"',
//            $userInfo['name'],
//            $info["now_playing"],
//            $info["name"]
//        ), url_for('@switch_channel?channelId='.$this->channelId, true));

        return $this->redirect('/dev.php' . '#' . $this->channelId);
	}
}
