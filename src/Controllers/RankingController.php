<?php

    require_once __DIR__ . "/../Models/Ranking.php";
    
    class RankingController extends RankingModel {

        public function __construct() {
            parent::__construct();
        }

        public function findRankingPoints() {
            try {
                $model = $this->rankingMorePoints();
                Flight::json([
                    'status' => 'success',
                    'data' => $model
                ]);
            } catch (Exception $e) {
                Flight::json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        }

    }