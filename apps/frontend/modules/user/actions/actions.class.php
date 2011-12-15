<?php

class userActions extends sfActions
{

	public function executeLogin(sfWebRequest $request)
	{
		$cF = new CylonFacebook();
		$fb = $cF->getFacebook();
		sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
		$this->loginUrl = $fb->getLoginUrl(array(
			'scope' => CylonFacebook::PERMISSIONS,
			'redirect_uri' => url_for('user/afterLogin', true)
		));
	}

	public function executeAfterLogin(sfWebRequest $request)
	{
		$cF = new CylonFacebook();
		$fb = $cF->getFacebook();
		$userId = $fb->getUser();
		if ($userId) {
			$this->getUser()->setAuthenticated(true);
		}

		$this->redirect('home/index');
	}
}
