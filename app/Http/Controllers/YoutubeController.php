<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class YoutubeController extends Controller
{
	public function get_youtube($link){
		$youtube = $link;
		$ch = curl_init($youtube);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// tat tu in
	    curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');// den tu gg.com
	    curl_setopt($ch, CURLOPT_ENCODING, '');// ACCETP ALL ENDCODING
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // thoi gian thuc thi
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // thoi gian ket noi den;
	    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.122 Safari/537.36');
	    $return = curl_exec($ch);
	    curl_close($ch);

	    return $return;
	}
  public function insertDetailVideo($videoId){
    $video_detail = [];
    $link = "https://www.youtube.com/watch?v=".$videoId;
    $content_detail_video = $this->getContent($link);
    $likeCount = 0;
    $dislikeCount = 0;
    $viewCount = 0;
    $description = NULL;
    $videoPrimaryInfoRenderer = NULL;
     
     //var_dump($content_detail_video->contents);
     //die();

    if(isset($content_detail_video->contents)){
    //get view count
    $results = $content_detail_video->contents->twoColumnWatchNextResults->results->results;
    if(isset($results->contents[0]->videoPrimaryInfoRenderer)){
      if(isset($results->contents[0]->videoPrimaryInfoRenderer->viewCount)){
             $viewCount_Object = $results->contents[0]->videoPrimaryInfoRenderer->viewCount->videoViewCountRenderer->viewCount;
      }
    }
    else{
      if(isset($results->contents[1]->videoPrimaryInfoRenderer->viewCount)){
          $viewCount_Object = $results->contents[1]->videoPrimaryInfoRenderer->viewCount->videoViewCountRenderer->viewCount;
      }
    }

    if(isset($viewCount_Object->runs[0]->text)){
      $viewCount = $viewCount_Object->runs[0]->text;
    }
    elseif(isset($viewCount_Object->simpleText)){
      $viewCount = $viewCount_Object->simpleText;
    }


    $viewCount = str_replace("lượt xem", "", $viewCount);
    $viewCount = str_replace("người đang xem", "", $viewCount);
    $viewCount = str_replace(".", "", $viewCount);
    $video_detail['viewCount'] = $viewCount;
    //end view count

    //description 
    if(isset($results->contents[1]->videoSecondaryInfoRenderer->description) || isset($results->contents[2]->videoSecondaryInfoRenderer->description) ){
      if(isset($results->contents[1]->videoSecondaryInfoRenderer)){
        $des = $results->contents[1]->videoSecondaryInfoRenderer->description;
        $list_des = $des->runs;
      }
      elseif (isset($results->contents[2]->videoSecondaryInfoRenderer)){
        $des = $results->contents[2]->videoSecondaryInfoRenderer->description;
        $list_des = $des->runs;
      }
      $description = [];
      foreach ($list_des as $a) {
      $description[] = $a->text;
      }
      $description = json_encode($description);
    }
    //var_dump($description);
    //var_dump(json_decode($description));
    //die();
    DB::table('tbl_video')->where('idvideo',$videoId)->update(['description' => $description]);
    //die();

    //end description
   
    // check like dislike
    if(isset($results->contents[0]->videoPrimaryInfoRenderer)){
      if(isset($results->contents[0]->videoPrimaryInfoRenderer->sentimentBar)){
             $videoPrimaryInfoRenderer = $results->contents[0]->videoPrimaryInfoRenderer;
            
      }
    }
    else{
      if(isset($results->contents[1]->videoPrimaryInfoRenderer->sentimentBar)){
          $videoPrimaryInfoRenderer = $results->contents[1]->videoPrimaryInfoRenderer;  
      }
    }

    if(isset($videoPrimaryInfoRenderer->sentimentBar)){
      $tooltip = $videoPrimaryInfoRenderer->sentimentBar->sentimentBarRenderer->tooltip;
      $tooltip = str_replace(" / ", " ", $tooltip);
      $dislike_like = explode(" ", $tooltip);
      //var_dump($dislike_like);
     
      $like_string = $dislike_like[0];
      $dislike_string = $dislike_like[1];
      $likeCount = str_replace(".", "", $like_string);
      $dislikeCount = str_replace(".", "", $dislike_string);
    }

    $video_detail['likeCount'] = $likeCount;
    $video_detail['dislikeCount'] = $dislikeCount;
    $id_video = DB::table('tbl_video')->where('idvideo', $videoId)->value('id');
    if($id_video != null && $id_video != ''){
        $video_detail['id_video'] = $id_video;
        DB::table('tbl_detail')->insertOrIgnore($video_detail);
    }
    //return true;
  }
  else{
    DB::table('tbl_video')->where('idvideo',$videoId)->delete();
  }

    //echo $dislikeCount; // luot ko thich 
    //echo $likeCount;  // luot thich 
  }
  public function getDataStatistics($videoID){
        $apikey = "AIzaSyCYHis2nud1qxR3l40FYo45WlX9vpbr1_k"; 
        $statisticsapi = "https://www.googleapis.com/youtube/v3/videos?part=statistics&id=" . $videoID . "&key=".$apikey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $statisticsapi);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);
        $value = json_decode(json_encode($data), true);
        return $value;
    }

	public function getContent($url){
		$data = $this->get_youtube($url);
		$pattern = '/[a-z]+\["[a-zA-Z]+\"]\s{1}\=.*/';
		preg_match($pattern, $data,$matches);
		$result = str_replace('window["ytInitialData"] = ', '', $matches[0]);
		$content =  substr($result, 0, -1);
		$content_decode = (json_decode($content));
		return $content_decode;
	}

	public function getAllDataPageByKeyWord($keyword){
		$url = "https://www.youtube.com/results?search_query=".$keyword;
		$content_decode = getContent($url);
  		$contents = $content_decode->contents->twoColumnSearchResultsRenderer->primaryContents->sectionListRenderer->contents[0]->itemSectionRenderer->contents; // data trang dau
	}

	public function getVideoRecentlyByKeyword($keyword){
		$url = "https://www.youtube.com/results?search_query=".$keyword;
    echo $url."\n";
		$content_decode = $this->getContent($url);

    if(isset($content_decode->contents->twoColumnSearchResultsRenderer->primaryContents->sectionListRenderer->contents[0]->itemSectionRenderer->contents[0]->carouselAdRenderer)){ 
      $contents = $content_decode->contents->twoColumnSearchResultsRenderer->primaryContents->sectionListRenderer->contents[1]->itemSectionRenderer->contents;
          // get data page 1
      $continuations = $content_decode->contents->twoColumnSearchResultsRenderer->primaryContents->sectionListRenderer->contents[1]->itemSectionRenderer->continuations[0]->nextContinuationData;
      $continuation = $continuations->continuation;
    }
    else{
      $contents = $content_decode->contents->twoColumnSearchResultsRenderer->primaryContents->sectionListRenderer->contents[0]->itemSectionRenderer->contents;
          // get data page 1
      $continuations = $content_decode->contents->twoColumnSearchResultsRenderer->primaryContents->sectionListRenderer->contents[0]->itemSectionRenderer->continuations[0]->nextContinuationData;
      $continuation = $continuations->continuation;
    }
    //echo $continuation;
     //echo "<br>";
     //$continuation = $continuations->continuation;
      $clickTrackingParams = $continuations->clickTrackingParams;

      $url_page_1 = "https://www.youtube.com/results?search_query=".$keyword."&ctoken=".$continuation."&continuation=".$continuation."&itct=".$clickTrackingParams;
      $conten_page1_decode = $this->getContent($url_page_1);
      $continuationContents_1 = $conten_page1_decode->continuationContents->itemSectionContinuation->contents;
      // noi dung list video page 1
      // echo "<pre>";
      // var_dump($continuationContents_1);
      // die();

      // get data page 2
      $nextContinuationData = $conten_page1_decode->continuationContents->itemSectionContinuation->continuations[0]->nextContinuationData;
      $continuation = $nextContinuationData->continuation;
      $clickTrackingParams = $nextContinuationData->clickTrackingParams;
      $url_page_2 = "https://www.youtube.com/results?search_query=".$keyword."&ctoken=".$continuation."&continuation=".$continuation."&itct=".$clickTrackingParams;
      $conten_page2_decode = $this->getContent($url_page_2);
      $continuationContents_2 = $conten_page2_decode->continuationContents->itemSectionContinuation->contents;



  $i = 0 ; 
  $j = 0;

  $video_info = [];
  echo "insert page 0"."\n";
  foreach ($contents as $content) {
  	if(isset($content->videoRenderer)){
          //echo ++$i."<br>";
          //echo $content->videoRenderer->videoId."<br>";
  		$video_info['idvideo'] = @$content->videoRenderer->videoId;
  		$video_info['title'] = @$content->videoRenderer->title->runs[0]->text;
  		$video_info['img'] = @$content->videoRenderer->thumbnail->thumbnails[0]->url;
  		$video_info['description'] = @$content->videoRenderer->descriptionSnippet->runs[0]->text;
  		$video_info['keyword'] = $keyword;
      //echo $video_info['idvideo'];
      //DB::table('tbl_video')->insertOrIgnore($video_info);
      if($video_info['idvideo'] != null && $video_info['idvideo'] != ''){
            DB::table('tbl_video')->insertOrIgnore($video_info);
            $this->insertDetailVideo($video_info['idvideo']);   
      }
  		//echo "<pre>";

  		//var_dump($video_info);
      //$statistics = $this->getDataStatistics($video_info['idvideo']);
           //var_dump($statistics['items'][0]['statistics']);
      //var_dump($statistics);
      // code api 
   /*   if (isset($statistics['error']) == false) {
        echo "loi roi";
      
      die();
      if(isset($statistics) && count($statistics)>0){
           $statistics_info['id_video'] =  $video_info['idvideo'];
           $statistics_info['viewCount'] = @$statistics['items'][0]['statistics']['viewCount'];
           $statistics_info['likeCount'] = @$statistics['items'][0]['statistics']['likeCount'];
           $statistics_info['dislikeCount'] = @$statistics['items'][0]['statistics']['dislikeCount'];
           $statistics_info['commentCount'] = @$statistics['items'][0]['statistics']['commentCount'];
           DB::table('tbl_statistics')->insertOrIgnore($statistics_info);
           DB::table('tbl_video')->insertOrIgnore($video_info);
        }
      }*/
  	}

  }
  echo "insert page 1"."\n";
  foreach ($continuationContents_1 as $content) {
  	if(isset($content->videoRenderer)){
  		$video_info['idvideo'] = @$content->videoRenderer->videoId;
  		$video_info['title'] = @$content->videoRenderer->title->runs[0]->text;
  		$video_info['img'] = @$content->videoRenderer->thumbnail->thumbnails[0]->url;
  		$video_info['description'] = @$content->videoRenderer->descriptionSnippet->runs[0]->text;
  		$video_info['keyword'] = $keyword;
      if($video_info['idvideo'] != null && $video_info['idvideo'] != ''){
             DB::table('tbl_video')->insertOrIgnore($video_info);
            $this->insertDetailVideo($video_info['idvideo']);   
      
      }
  	}
  }

  echo "insert page 2"."\n";
  foreach ($continuationContents_2 as $content) {
  	if(isset($content->videoRenderer)){
  		$video_info['idvideo'] = $content->videoRenderer->videoId;
  		$video_info['title'] = @$content->videoRenderer->title->runs[0]->text;
  		$video_info['img'] = @$content->videoRenderer->thumbnail->thumbnails[0]->url;
  		$video_info['description'] = @$content->videoRenderer->descriptionSnippet->runs[0]->text;
  		$video_info['keyword'] = $keyword;

     if($video_info['idvideo'] != null && $video_info['idvideo'] != ''){
            DB::table('tbl_video')->insertOrIgnore($video_info);
            $this->insertDetailVideo($video_info['idvideo']);    
      }
  	}
  }

}


}
