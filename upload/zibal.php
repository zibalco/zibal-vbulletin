<?php

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
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response  = curl_exec($ch);

    curl_close($ch);
    return json_decode($response);
}
	$res = postToZibal('request',
					array(
						'merchant' 	=> $_POST['zibal_mid'],
						'amount' 	=> $_POST['zibal_amount'],
						'description' 	=> $_POST['zibal_comments'],
						'callbackUrl' 	=> $_POST['zibal_callback_url']
						)
					);
					var_dump($res);
	if($res->result == 100 ){
		Header('Location: https://gateway.zibal.ir/start/' . $res->trackId.'/direct');
	} else {
		echo'ERR: '. $res->result;
	}
?>
