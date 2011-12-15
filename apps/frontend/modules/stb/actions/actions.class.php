<?php

class stbActions extends sfActions
{

	public function executeSwitch(sfWebRequest $request)
	{
		$this->channelId = $request->getParameter('channelId', null);

		$cF = new CylonFacebook();
		$boxMapping = new BoxMapping();
		$boxIP = $boxMapping->getBoxIp($cF->getUserId());

		if (null === $boxIP) {
			$this->redirect('home/buy');
		}

		$stb = new STB($boxIP);
		$stb->ChannelChange($this->channelId);
	}
}
