<?php

if (!function_exists('curl_init')) {
    throw new Exception('Playbasis needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
    throw new Exception('Playbasis needs the JSON PHP extension.');
}

class Playbasis
{
    const BASE_URL = 'https://api.pbapp.net/';
    const BASE_ASYNC_URL = 'https://api.pbapp.net/async/';

    private $token = null;
    private $apiKeyParam = null;
    private $respChannel = null;

    /**
     * Authentication procedure on playbasis
     * @param $apiKey
     * @param $apiSecret
     * @return true if request is successful
     */
    public function auth($apiKey, $apiSecret)
    {
        $this->apiKeyParam = "?api_key=$apiKey";
        $result = $this->call('Auth', array(
            'api_key' => $apiKey,
            'api_secret' => $apiSecret
        ));
        $this->token = $result['response']['token'];
        return $this->token != false && is_string($this->token);
    }

    /**
     * Renew the authentification
     * @param $apiKey
     * @param $apiSecret
     * @return true if request is successful
     */
    public function renew($apiKey, $apiSecret)
    {
        $this->apiKeyParam = "?api_key=$apiKey";
        $result = $this->call('Auth/renew', array(
            'api_key' => $apiKey,
            'api_secret' => $apiSecret
        ));
        $this->token = $result['response']['token'];
        return $this->token != false && is_string($this->token);
    }

    /*
     * @param	$channel	Set this value to the domain of your site (ex. yoursite.com) to receive the response of async calls via our response server.
     *						Please see our api documentation at doc.pbapp.net for more details.
     */
    public function setAsyncResponseChannel($channel)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_ASYNC_URL."channel/verify/$channel");
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Playbasis SDK php');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        if($result === 'true')
        {
            $this->respChannel = $channel;
            return true;
        }
        return false;
    }

    /**
     * Get information for a player. Fields include
     * image
     * email
     * username
     * exp
     * level
     * first_name
     * last_name
     * gender
     * birth_date
     * registered
     * last_login
     * last_logout
     * cl_player_id
     * @param $playerId
     * @return
     */
    public function player($playerId)
    {
        return $this->call("Player/$playerId", array('token' => $this->token));
    }

    /*
     * $playerListId player id as used in client's website separate with ',' example '1,2,3'
     */
    public function playerList($playerListId)
    {
        return $this->call("Player/list", array('token' => $this->token, 'list_player_id' => $playerListId));
    }

    /*
     * Get detailed information about a player, including points and badges
     * @param $playerId
     */
    public function playerDetail($playerId)
    {
        return $this->call("Player/$playerId/data/all", array('token' => $this->token));
    }

    /*
     * @param	$optionalData	Key-value for additional parameters to be sent to the register method.
     * 							The following keys are supported:
     * 							- facebook_id
     * 							- twitter_id
     * 							- password		assumed hashed
     * 							- first_name
     * 							- last_name
     * 							- nickname
     * 							- gender		1=Male, 2=Female
     * 							- birth_date	format YYYY-MM-DD
     */
    public function register($playerId, $username, $email, $imageUrl, $optionalData=array())
    {
        return $this->call("Player/$playerId/register", array_merge(array(
            'token' => $this->token,
            'username' => $username,
            'email' => $email,
            'image' => $imageUrl
        ), $optionalData));
    }
    public function register_async($playerId, $username, $email, $imageUrl, $optionalData=array())
    {
        return $this->call_async("Player/$playerId/register", array_merge(array(
            'token' => $this->token,
            'username' => $username,
            'email' => $email,
            'image' => $imageUrl
        ), $optionalData), $this->respChannel);
    }

    /*
     * @param	$updateData		Key-value for data to be updated.
     * 							The following keys are supported:
     *							- username
     *							- email
     *							- image
     *							- exp
     *							- level
     * 							- facebook_id
     * 							- twitter_id
     * 							- password		assumed hashed
     * 							- first_name
     * 							- last_name
     * 							- nickname
     * 							- gender		1=Male, 2=Female
     * 							- birth_date	format YYYY-MM-DD
     */
    public function update($playerId, $updateData)
    {
        $updateData['token'] = $this->token;
        return $this->call("Player/$playerId/update", $updateData);
    }
    public function update_async($playerId, $updateData)
    {
        $updateData['token'] = $this->token;
        return $this->call_async("Player/$playerId/update", $updateData, $this->respChannel);
    }

    /*
     * Delete a player
     * @param $playerId
     */
    public function delete($playerId)
    {
        return $this->call("Player/$playerId/delete", array('token' => $this->token));
    }
    public function delete_async($playerId)
    {
        return $this->call_async("Player/$playerId/delete", array('token' => $this->token), $this->respChannel);
    }

    /*
     * Call login action on server
     * @param $playerId
     */
    public function login($playerId)
    {
        return $this->call("Player/$playerId/login", array('token' => $this->token));
    }
    public function login_async($playerId)
    {
        return $this->call_async("Player/$playerId/login", array('token' => $this->token), $this->respChannel);
    }

    /*
     * Call logout action on server
     * @param $playerId
     */
    public function logout($playerId)
    {
        return $this->call("Player/$playerId/logout", array('token' => $this->token));
    }
    public function logout_async($playerId)
    {
        return $this->call_async("Player/$playerId/logout", array('token' => $this->token), $this->respChannel);
    }

    /*
     * Returns information about all point-based rewards that a player currently have.
     * @param $playerId
     */
    public function points($playerId)
    {
        return $this->call("Player/$playerId/points" . $this->apiKeyParam);
    }

    /*
     * Returns how much of specified the point-based reward a player currently have.
     * @param $playerId
     * @param $pointName
     */
    public function point($playerId, $pointName)
    {
        return $this->call("Player/$playerId/point/$pointName" . $this->apiKeyParam);
    }

    /*
     * Returns reward a player currently have.
     * @param $playerId
     * @param $pointName
     * @param $offset
     * @param $limit
     */
    public function pointHistory($playerId, $pointName='', $offset=0, $limit=20)
    {
        $string_query = '&offset='.$offset.'&limit='.$limit;
        if($pointName != '')$string_query = $string_query."&point_name=".$pointName;
        return $this->call("Player/$playerId/point_history" . $this->apiKeyParam . $string_query);
    }

    /*
     * Returns the time and action that a player last performed.
     * @param $playerId
     */
    public function actionLastPerformed($playerId)
    {
        return $this->call("Player/$playerId/action/time" . $this->apiKeyParam);
    }

    /*
     * Returns the last time that player performed the specified action.
     * @param $playerId
     * @param $actionName
     */
    public function actionLastPerformedTime($playerId, $actionName)
    {
        return $this->call("Player/$playerId/action/$actionName/time" . $this->apiKeyParam);
    }

    /*
     * Returns the number of times that a player has performed the specified action.
     * @param $playerId
     * @param $actionName
     */
    public function actionPerformedCount($playerId, $actionName)
    {
        return $this->call("Player/$playerId/action/$actionName/count" . $this->apiKeyParam);
    }

    /*
     * Returns information about all the badges that a player has earned.
     * @param $playerId
     */
    public function badgeOwned($playerId)
    {
        return $this->call("Player/$playerId/badge" . $this->apiKeyParam);
    }

    /*
     * Returns list of top players according to specified point type.
     * @param $rankedBy
     * @param $limit
     */
    public function rank($rankedBy, $limit)
    {
        return $this->call("Player/rank/$rankedBy/$limit" . $this->apiKeyParam);
    }

    /*
     * Returns list of top players.
     * @param $limit
     */
    public function ranks($limit)
    {
        return $this->call("Player/ranks/$limit" . $this->apiKeyParam);
    }

    /*
     * Returns information of all levels.
     */
    public function levels()
    {
        return $this->call("Player/levels" . $this->apiKeyParam);
    }

    /*
     * Returns information about specified level.
     * @param $lv
     */
    public function level($level)
    {
        return $this->call("Player/level/$level" . $this->apiKeyParam);
    }

    public function claimBadge($playerId, $badgeId)
    {
        return $this->call("Player/$playerId/badge/$badgeId/claim", array('token' => $this->token));
    }

    public function redeemBadge($playerId, $badgeId)
    {
        return $this->call("Player/$playerId/badge/$badgeId/redeem", array('token' => $this->token));
    }

    /*
     * Returns information about all the goods list that a player has redeem.
     * @param $playerId player id as used in client's website
     */
    public function goodsOwned($playerId)
    {
        return $this->call("Player/$playerId/goods" . $this->apiKeyParam);
    }

    public function questOfPlayer($playerId, $quest_id)
    {
        return $this->call("Player/quest/$quest_id" . $this->apiKeyParam . "&player_id=" . $playerId);
    }

    public function questListOfPlayer($playerId)
    {
        return $this->call("Player/quest". $this->apiKeyParam . "&player_id=" . $playerId);
    }

    /*
     * Returns information about all available badges for the current site.
     */
    public function badges()
    {
        return $this->call("Badge" . $this->apiKeyParam);
    }

    /*
     * Returns information about the badge with the specified id.
     * @param $badgeId
     */
    public function badge($badgeId)
    {
        return $this->call("Badge/$badgeId" . $this->apiKeyParam);
    }

    /*
     * Returns information about all available goods for the current site.
     */
    public function goodsList()
    {
        return $this->call("Goods" . $this->apiKeyParam);
    }

    /*
     * Returns information about the goods with the specified id.
     * @param goodId
     */
    public function goods($goodsId)
    {
        return $this->call("Goods/$goodsId" . $this->apiKeyParam);
    }

    /**
     * Returns names of actions that can trigger game rules within a client’s website.
     */
    public function actionConfig()
    {
        return $this->call("Engine/actionConfig" . $this->apiKeyParam);
    }

    /*
     * @param	$optionalData	Key-value for additional parameters to be sent to the rule method.
     * 							The following keys are supported:
     * 							- url		url or filter string (for triggering non-global actions)
     * 							- reward	name of the custom-point reward to give (for triggering rules with custom-point reward)
     * 							- quantity	amount of points to give (for triggering rules with custom-point reward)
     */
    public function rule($playerId, $action, $optionalData=array())
    {
        return $this->call("Engine/rule", array_merge(array(
            'token' => $this->token,
            'player_id' => $playerId,
            'action' => $action
        ), $optionalData));
    }
    public function rule_async($playerId, $action, $optionalData=array())
    {
        return $this->call_async("Engine/rule", array_merge(array(
            'token' => $this->token,
            'player_id' => $playerId,
            'action' => $action
        ), $optionalData), $this->respChannel);
    }

    /**
     * Returns information about all quest for the current site.
     */
    public function quests()
    {
        return $this->call("Quest" . $this->apiKeyParam);
    }

    /*
     * Returns information about the quest with the specified id.
     * @param $questId
     */
    public function quest($questId)
    {
        return $this->call("Quest/$questId" . $this->apiKeyParam);
    }

    /*
     * Returns information about mission with the specified id.
     * @param $questId
     * @param $missionId
     */
    public function mission($questId, $missionId)
    {
        return $this->call("Quest/$questId/mission/$missionId" . $this->apiKeyParam);
    }

    /* Returns information about list of quest is available for player. */
    public function questsAvailable($playerId)
    {
        return $this->call("Quest/available" . $this->apiKeyParam . "&player_id=" . $playerId);
    }

    /* check the quest is available/unavailable for player. */
    public function questAvailable($questId, $playerId)
    {
        return $this->call("Quest/$questId/available" . $this->apiKeyParam . "&player_id=" . $playerId);
    }

    public function joinQuest($questId, $playerId)
    {
        return $this->call("Quest/$questId/join", array(
            'token' => $this->token,
            'player_id' => $playerId
        ));
    }
    public function joinQuest_async($questId, $playerId)
    {
        return $this->call_async("Quest/$questId/join", array(
            'token' => $this->token,
            'player_id' => $playerId
        ), $this->respChannel);
    }

    public function cancelQuest($questId, $playerId)
    {
        return $this->call("Quest/$questId/cancel", array(
            'token' => $this->token,
            'player_id' => $playerId
        ));
    }
    public function cancelQuest_async($questId, $playerId)
    {
        return $this->call_async("Quest/$questId/cancel", array(
            'token' => $this->token,
            'player_id' => $playerId
        ), $this->respChannel);
    }

    public function redeemGoods($goodsId, $playerId, $amount=1)
    {
        return $this->call("Redeem/goods", array(
            'token' => $this->token,
            'goods_id' => $goodsId,
            'player_id' => $playerId,
            'amount' => $amount,
        ));
    }
    public function redeemGoods_async($goodsId, $playerId, $amount=1)
    {
        return $this->call_async("Redeem/goods", array(
            'token' => $this->token,
            'goods_id' => $goodsId,
            'player_id' => $playerId,
            'amount' => $amount,
        ), $this->respChannel);
    }

    public function recentPoint($offset=0, $limit=10)
    {
        return $this->call("Service/recent_point" . $this->apiKeyParam ."&offset=".$offset."&limit=".$limit);
    }

    public function recentPointByName($point_name, $offset=0, $limit=10)
    {
        return $this->call("Service/recent_point" . $this->apiKeyParam  ."&offset=".$offset."&limit=".$limit."&point_name=".$point_name);
    }

    public function call($method, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL . $method);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);					// turn off output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);				// refuse response from called server
        curl_setopt($ch, CURLOPT_USERAGENT, 'Playbasis SDK php');			// set agent
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);						// times for execute
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);				// times for try to connect
        if($data)
        {
            curl_setopt($ch, CURLOPT_POST, TRUE);					// use POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);			// data
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        curl_close($ch);
        return $result;
    }

    /*
     * @param	$responseChannel	Set this value to the domain of your site (ex. yoursite.com) to receive the response of async calls via our response server.
     *								Please see our api documentation at doc.pbapp.net for more details.
     */
    public function call_async($method, $data, $responseChannel = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::BASE_ASYNC_URL.'call');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);					// turn off output
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);				// refuse response from called server
        curl_setopt($ch, CURLOPT_USERAGENT, 'Playbasis SDK php');			// set agent
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);						// times for execute
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);				// times for try to connect
        if($data)
        {
            $body['endpoint'] = $method;
            $body['data'] = $data;
            if($responseChannel)
                $body['channel'] = $responseChannel;
            $body = json_encode($body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8'		// set Content-Type
            ));
            curl_setopt($ch, CURLOPT_POST, TRUE);					// use POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);			// post body
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}