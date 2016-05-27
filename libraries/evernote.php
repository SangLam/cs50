<?php 
	/*hold the class interface for interacting with evernote*/

	require __DIR__ . '/../vendors/evernote/vendor/autoload.php';
	
	class Evernote {
		
		private static $token;
		private static $sandbox;
		private static $china;
		private static $client;
		
		public static function init($token, $sandbox, $china = false) {
			self::$token = $token;
			self::$sandbox = $sandbox;
			self::$china = $china;
		}
		
		private static function getClient() {
			if (!isset(self::$client))
				self::$client = new \Evernote\Client(self::$token, self::$sandbox, null, null, self::$china);
			return self::$client;
		}
				

		public static function getTitleWithTerm($term) {
			
			$search = new \Evernote\Model\Search($term);
			$notebook = NULL;
			$scope = 'PERSONAL_SCOPE';
			$order = \Evernote\Client::SORT_ORDER_REVERSE | \Evernote\Client::SORT_ORDER_RECENTLY_CREATED;
			$maxResult = 5;
			
			$results = self::getClient()->findNotesWithSearch($search, $notebook, $scope, $order, $maxResult);
			
			$titles = array();
			foreach ($results as $result) {
				array_push($titles, ['title' => self::getClient()->getNote($result->guid)->getTitle()]);
			}
			header("Content-type: application/json");
			echo json_encode($titles, JSON_PRETTY_PRINT);
		}
		
		private static function hasMedia($content) {}
		
	}
?>