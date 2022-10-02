<?php
$cookies = readline("?Cookie\t");
$slugs = getLearns();
for($z=0;$z<count($slugs);$z++){
	$slugd = $slugs[$z];
	$slug = $slugd['slug'];
	echo "[$slug]\n";
	if($slugd['count_video_finished'] == $slugd['count_video']){
		echo "\tAlready Learned. Skip!!\n";
		continue;
	}
	$is = getInfoFromSlug($slug);
	if(!is_array($is)) continue;
	$arr = array();
	for($a=0;$a<count($is);$a++){
		Awal:
		$iss = $is[$a];
		$li = $iss['id'];
		$ul = updateLearn($li);
		echo "\t[$li] $ul\n";
		if($ul == 'Too Many Attempts.'){
			sleeps(30, "\t\tWait For ");
			goto Awal;
		}
		$quizz = $iss['quiz'];
		if(is_array($quizz)){
			$qi = $quizz['id'];
			$gqs = getQuiz($qi);
			if(!is_array($gqs)) continue;
			for($b=0;$b<count($gqs);$b++){
				$gq = $gqs[$b];
				$qqi = $gq['id'];
				$qa = $gq['quiz_answer'];
				for($c=0;$c<count($qa);$c++){
					$qaii = $qa[$c]['id'];
					$qais[$c] = $qaii;
					$qads[$qaii] = $qa[$c]['answer'];
				}
				$qai = $qais[rand(0, count($qais)-1)];
				UQ:
				$uq = updateQuiz($li, $qqi, $qai);
				if($uq['message'] == 'Too Many Attempts.'){
					$wt = $uq['result']['retry_after'];
					sleeps($wt, "\t\tWait For ");
					goto UQ;
				}elseif($uq['message'] == 'Unable to submit quiz answer!'){
					continue;
				}
				$ca = $uq['quiz']['quiz_question'][0]['correct_answer_id'];
				$qad = $qads[$ca];
				$iiiii = @array("id" => $qi, "data" => array("ad" => $qad, "ca" => $ca));
				$arr[$li][] = $iiiii;
				echo "\t\t".@json_encode($iiiii)."\n";
			}
		}else{
			$arr[$li] = null;
		}
	}
	@file_put_contents("playquest.txt", @json_encode($arr)."\n", FILE_APPEND);
}


function sleeps($time, $ext = null){
	$t = "";
	anjay:
		if($time<10) $t = "0";
		echo $ext.$t.$time."\r";
		$time -= 1;
		sleep(1);
		if($time>0) goto anjay;
	return true;
}

function getLearns(){
	return @json_decode(curl("learns"), true)['result'];
}

function getInfoFromSlug($slug){
	return @json_decode(@curl("dashboard/learn-detail-v2/$slug"), true)['learn']['learn_contents'];
}

function updateLearn($li){
	return @json_decode(@curl("dashboard/update-learn-video-progress", '{"learn_content_id":'.$li.',"last_watched_at":0,"is_video_finished":true}'), true)['message'];
}

function getQuiz($qi){
	return @json_decode(@curl("dashboard/learn-quiz/$qi"), true)['quiz']['quiz_question'];
}

function updateQuiz($li, $qqi, $qai){
	return @json_decode(@curl("dashboard/submit-learn-content-quiz-answer", '{"learn_content_id":'.$li.',"is_timeout":false,"member_quiz_answers":[{"quiz_question_id":'.$qqi.',"quiz_answer_id":"'.$qai.'"}]}'), true);
}

function curl($path, $body = false, $headers = array()){
	global $cookies;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://awsapi.play3.gg/api/'.$path);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if($body){
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	}
	$headers[] = 'Host: awsapi.play3.gg';
	$headers[] = 'Access-Control-Allow-Origin: *';
	$headers[] = 'Accept: application/json';
	$headers[] = 'X-Requested-With: XMLHttpRequest';
	$headers[] = 'Authorization: Bearer '.$cookies;
	$headers[] = 'User-Agent: Mozilla/5.0 (Linux; Android 9) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/105.0.5195.136 Mobile DuckDuckGo/5 Safari/537.36';
	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Origin: https://app.play3.gg';
	$headers[] = 'Referer: https://app.play3.gg/';
	$headers[] = 'Accept-Language: en-US,en;q=0.9';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
