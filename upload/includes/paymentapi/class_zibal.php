<?php

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

/**
* Class that provides payment verification and form generation functions
*
* @package	vBulletin
* @version	$Revision: 20000 $
* @date		$Date: 2012-03-25 01:24:45 +0350 (Sun, 25 March 2012) $
*/
class vB_PaidSubscriptionMethod_zibal extends vB_PaidSubscriptionMethod
{
	var $supports_recurring = false;	 
	var $display_feedback = true;
    /**
     * connects to zibal's rest api
     * @param $path
     * @param $parameters
     * @return stdClass
     */
    function postToZibal($path, $parameters)
    {
        $url = 'https://gateway.zibal.ir/v1/'.$path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

    function verify_payment()
	{		
		$this->registry->input->clean_array_gpc('r', array(
			'item'		=> TYPE_STR,			
			'trackId'	=> TYPE_STR,
			'success'	=> TYPE_STR
		));
		
		$this->transaction_id = $this->registry->GPC['trackId'];
		if(!empty($this->registry->GPC['item']) AND !empty($this->registry->GPC['trackId']))
		{
			$this->paymentinfo = $this->registry->db->query_first("
				SELECT paymentinfo.*, user.username
				FROM " . TABLE_PREFIX . "paymentinfo AS paymentinfo
				INNER JOIN " . TABLE_PREFIX . "user AS user USING (userid)
				WHERE hash = '" . $this->registry->db->escape_string($this->registry->GPC['item']) . "'
			");
			if (!empty($this->paymentinfo) && $this->registry->GPC['success'] == '1')
			{
				$sub = $this->registry->db->query_first("SELECT * FROM " . TABLE_PREFIX . "subscription WHERE subscriptionid = " . $this->paymentinfo['subscriptionid']);
				$cost = unserialize($sub['cost']);				
				$amount = floor($cost[0][cost][usd]*$this->settings['d2t']);
				$res = $this->postToZibal('verify',
					array(
						'merchant'	 => $this->settings['zibalmid'],
						'trackId' 	 => $this->registry->GPC['trackId'],
					));

				if($amount==$res->amount && $res->result == 100)
				{
					$this->paymentinfo['currency'] = 'usd';
					$this->paymentinfo['amount'] = $cost[0][cost][usd];				
					$this->type = 1;								
					return true;					
				} else {
					$this->error = 'ERR: '. $res->result;
					return false;
				}				
			} else {
				$this->error = 'Invalid trasaction';
				return false;	
			}
		}else{		
			$this->error = 'Duplicate transaction.';
			return false;
		}
    }


	function generate_form_html($hash, $cost, $currency, $subinfo, $userinfo, $timeinfo)
	{
		global $vbphrase, $vbulletin, $show;
        
		$item = $hash;		
		$cost = floor($cost*$this->settings['d2t']);		
		$merchant = $this->settings['zibalmid'];
		$form['action'] = 'zibal.php';
		$form['method'] = 'POST';        
			
		$settings =& $this->settings;
		
		$templater = vB_Template::create('subscription_payment_zibal');
	     	$templater->register('merchantID', $merchant);
		$templater->register('cost', $cost);
		$templater->register('item', $item);					
		$templater->register('subinfo', $subinfo);
		$templater->register('settings', $settings);
		$templater->register('userinfo', $userinfo);
		$form['hiddenfields'] .= $templater->render();
		return $form;
	}
}
?>
