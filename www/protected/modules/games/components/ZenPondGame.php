<?php

class ZenPondGame extends MGGame implements MGGameInterface {
  public $two_player_game = true;
  
  public function parseSubmission(&$game, &$game_model) {
    $game->request->submissions = array();  
    
    $success = true;
    
    if (isset($_POST["submissions"]) && is_array($_POST["submissions"]) && count($_POST["submissions"]) > 0) {
      foreach ($_POST["submissions"] as $submission) {
        if ($submission["image_id"] && (int)$submission["image_id"] != 0
          && $submission["tags"] && (string)$submission["tags"] != "") {
          $game->request->submissions[] = $submission;
        } 
      }
    }
    $success = (count($game->request->submissions) > 0);
    
    $plugins = PluginsModule::getActiveGamePlugins($game->game_id, "dictionary");
    if (count($plugins) > 0) {
      foreach ($plugins as $plugin) {
        if (method_exists($plugin->component, "parseSubmission")) {
          $success = $success  && $plugin->component->parseSubmission($game, $game_model);
        }
      }
    }
    
    $plugins = PluginsModule::getActiveGamePlugins($game->game_id, "weighting");
    if (count($plugins) > 0) {
      foreach ($plugins as $plugin) {
        if (method_exists($plugin->component, "parseSubmission")) {
          $success = $success  && $plugin->component->parseSubmission($game, $game_model);
        }
      }
    }
    
    return $success;
  }
    
  public function getTurn(&$game, &$game_model, $tags=array()) {
    if ($game->played_against_computer) {
      return $this->_createTurn($game, $game_model, $tags);
    } else {
      $turn = $this->loadTwoPlayerTurnFromDb($game->played_game_id, $game->turn + 1);
      if (is_null($turn)) {
        $api_id = Yii::app()->fbvStorage->get("api_id", "MG_API");
        $turn = $this->_createTurn($game, $game_model, $tags);
        
        // it might happen that for both user it might appear to be the first one to read the table
        // thus the next statement check whether the turn has been saved for this played game and turn
        // if it has been a unique exception forces the second user to load the turn from the db
        // can't use table locks as $game_engine->getTurn uses various and potentually unknown tables that would all have to 
        // be included into the lock statement
        if (!$this->saveTwoPlayerTurnToDb($game->played_game_id, $game->turn + 1, (int)Yii::app()->session[$api_id .'_SESSION_ID'], $turn)) {
          $turn = $this->loadTwoPlayerTurnFromDb($game->played_game_id, $game->turn + 1); 
        }
      }
    }
    
    if (is_null($turn)) {
      throw new CHttpException(500, Yii::t('app', 'Internal Server Error.'));
    }
    return $turn;
  }  
    
  private function _createTurn(&$game, &$game_model, $tags=array()) {
    $data = array();
    if ($game->turn < $game->turns) {
      $imageSets = $this->getImageSets($game, $game_model);
    
      $data["images"] = array();
      
      $used_images = array();
      $images = $this->getImages($imageSets, $game, $game_model);
      
      if ($images && count($images) > 0) {
        $i = array_rand($images, 1);
        
        $path = Yii::app()->getBaseUrl(true) . Yii::app()->fbvStorage->get('settings.app_upload_url');
        $data["images"][] = array(
          "image_id" => $images[$i]["id"],
          "full_size" => $path . "/images/". $images[$i]["name"],
          "thumbnail" => $path . "/thumbs/". $images[$i]["name"],
          "final_screen" => $path . "/scaled/". MGHelper::createScaledImage($images[$i]["name"], "", "scaled", 212, 171, 80, 10),
          "scaled" => $path . "/scaled/". MGHelper::createScaledImage($images[$i]["name"], "", "scaled", $game->image_width, $game->image_height, 80, 10),
          "licences" => $images[$i]["licences"],
        );
        $used_images[] = (int)$images[$i]["id"];
        
        $data["licences"] = $this->getLicenceInfo($images[$i]["licences"]);
        
        $this->setUsedImages($used_images, $game, $game_model);

        $data["tags"] = array();
        $data["tags"]["user"] = $tags;
        $data["wordstoavoid"] = array();
        
        $plugins = PluginsModule::getActiveGamePlugins($game->game_id, "dictionary");
        if (count($plugins) > 0) {
          foreach ($plugins as $plugin) {
            if (method_exists($plugin->component, "wordsToAvoid")) {
              // this method gets all elements by reference. $data["wordstoavoid"] might be changed
              $plugin->component->wordsToAvoid($data["wordstoavoid"], $used_images, $game, $game_model, $tags);
            }
          }
        }
        
      } else 
        throw new CHttpException(600, $game->name . Yii::t('app', ': Not enough images available'));
      
    } else {
      $data["tags"] = array();
      $data["tags"]["user"] = $tags;
      $data["licences"] = array();
    } 
    throw new CHttpException(600, $game->name . Yii::t('app', ': xxx'));
    return $data;
  }
  
  public function setWeights(&$game, &$game_model, $tags) {
    $plugins = PluginsModule::getActiveGamePlugins($game->game_id, "dictionary");
    if (count($plugins) > 0) {
      foreach ($plugins as $plugin) {
        if (method_exists($plugin->component, "setWeights")) {
          $tags = $plugin->component->setWeights($game, $game_model, $tags);
        }
      }
    }
    
    $plugins = PluginsModule::getActiveGamePlugins($game->game_id, "weighting");
    if (count($plugins) > 0) {
      foreach ($plugins as $plugin) {
        if (method_exists($plugin->component, "setWeights")) {
          $tags = $plugin->component->setWeights($game, $game_model, $tags);
        }
      }
    }
    return $tags;
  }
  
  public function getScore(&$game, &$game_model, &$tags) {
    $score = 0;
    $plugins = PluginsModule::getActiveGamePlugins($game->game_id, "weighting");
    if (count($plugins) > 0) {
      foreach ($plugins as $plugin) {
        if (method_exists($plugin->component, "score")) {
          $score = $plugin->component->score($game, $game_model, $tags, $score);
        }
      }
    }
    return $score;
  }
  
  public function parseTags(&$game, &$game_model) {
    $data = array();
    $image_ids = array();
    foreach ($game->request->submissions as $submission) {
      $image_ids[] = $submission["image_id"];
      $image_tags = array();
      foreach (MGTags::parseTags($submission["tags"]) as $tag) {
        $image_tags[strtolower($tag)] = array(
          'tag' => $tag,
          'weight' => 1,
          'type' => 'new',
          'tag_id' => 0
        );
      }
      $data[$submission["image_id"]] = $image_tags;
    }
    
    if (!$game->played_against_computer && $this->two_player_game && isset($game->opponents_submission) && is_array($game->opponents_submission)) {
      // it is really a two player game and we have to parse the oppenents_submission to make the tags info available for later use
      
      $game->opponents_submission["parsed"] = array();
      
      foreach ($game->opponents_submission as $image) {
        if (is_object($image)) {
          $image_ids[] = $image->image_id;
        
          $image_tags = array();
          foreach (MGTags::parseTags($image->tags) as $tag) {
            $image_tags[strtolower($tag)] = array(
              'tag' => $tag,
              'weight' => 1,
              'type' => 'new',
              'tag_id' => 0
            );
          }
          $game->opponents_submission["parsed"][$image->image_id] = $image_tags;
        }
      }
    }
    
    $image_tags = MGTags::getTags($image_ids);
    foreach ($data as $submitted_image_id => $submitted_image_tags) {
      foreach ($submitted_image_tags as $submitted_tag => $sval) {
        if (isset($image_tags[$submitted_image_id])) {
          foreach ($image_tags[$submitted_image_id] as $image_tag_id => $ival) {
            if ($submitted_tag == strtolower($ival["tag"])) {
              $data[$submitted_image_id][$submitted_tag]['type'] = 'match';
              $data[$submitted_image_id][$submitted_tag]['tag_id'] = $image_tag_id;
              break;
            }
          }          
        }
      }
    }
    
    if (!$game->played_against_computer && $this->two_player_game && isset($game->opponents_submission) && is_array($game->opponents_submission["parsed"])) {
      foreach ($game->opponents_submission["parsed"] as $submitted_image_id => $submitted_image_tags) {
        foreach ($submitted_image_tags as $submitted_tag => $sval) {
          if (isset($image_tags[$submitted_image_id])) {
            foreach ($image_tags[$submitted_image_id] as $image_tag_id => $ival) {
              if ($submitted_tag == strtolower($ival["tag"])) {
                $game->opponents_submission["parsed"][$submitted_image_id][$submitted_tag]['type'] = 'match';
                $game->opponents_submission["parsed"][$submitted_image_id][$submitted_tag]['tag_id'] = $image_tag_id;
                break;
              }
            }          
          }
        }
      }
    }
    return $data;
  }
}
