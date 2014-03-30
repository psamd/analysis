<?php
	
	error_reporting(0);

	$file_pageNo = "files/pageNo.txt";
	$file_tags = "files/tags.json";

	include 'includes/connectDB.inc';
				
	$conn = connectDB();
	//$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				
				
	if (isset($_GET['q'])) {
	
		//This loads the page no. to load at the start of application, based on the page last seen
		if ($_GET['q'] == "pageNo") {
		
			$number = file_get_contents($file_pageNo);
			if ($number === FALSE) {
				$number = 0;
			}
			echo $number;
			
		}
	
		//This is to get the list of papers already marked as relevant or non relevant
		if ($_GET['q'] == "initial_relevance") {
			$table = $_GET['value'];
			$sql = "SELECT * FROM $table";
			
			try {
				$q = $conn -> prepare($sql);
				$q -> execute() or die("failed-execute");
			}
			catch (PDOException $e) {
				//Do your error handling here
				echo $e->getMessage();
			}
			
			$result = $q->fetchAll(PDO::FETCH_COLUMN, 0);
			
			echo json_encode($result);
		}
	
		//This is to mark if the paper is relevant or not and add it to DB
		if ($_GET['q'] == "relevance") {
		
			$table = "";
			if ($_GET['type'] == "yes") {
				$table = "relevant";
			}
			if ($_GET['type'] == "no") {
				$table = "non_relevant";
			}
			if ($_GET['type'] == "wrong_paper") {
				$table = "wrong_paper";
			}
		
			$id = $_GET['value'];
			$sql = "INSERT INTO $table (pmid) VALUES (?)";
			
			try {
				$q = $conn -> prepare($sql);
				$q -> execute(array($id)) or die("failed-execute");
			}
			catch (PDOException $e) {
				//Do your error handling here
				echo $e->getMessage();
			}
			
			$num = $_GET['pageNo'];
			$initial_num = file_get_contents($file_pageNo);
			$initial_num = intval($initial_num);
			
			if ($initial_num > 0) {
				if ($num > $initial_num) {
					file_put_contents($file_pageNo, $num);
				}
			}
			else {
				file_put_contents($file_pageNo, $num);
			}
		}
		
		
		//To add gene to database - By executing Perl script
		if ($_GET['q'] == "add_gene") {
			$name = $_GET['gene_name'];
			echo shell_exec("perl programs/Bioperl/getGene_genbank.pl $name");
		}
		
		//To add drug to database
		if ($_GET['q'] == "add_drug") {
			$name = $_GET['drug_name'];
			$category = $_GET['drug_category'];
			$resistant = $_GET['drug_resistant'];
			
			empty($category) ? null : $category;
			empty($resistant) ? null : $resistant;
			
			$sql = "INSERT INTO drugs (name, category, resistant) VALUES (:n, :c, :r)";
			$q = $conn -> prepare($sql);
			$q->bindParam(':n', $name);
			$q->bindParam(':c', $category);
			$q->bindParam(':r', $resistant);
			$q->execute();
		}
		
		
		//To get list of existing tags
		if ($_GET['q'] == "tags") {
			$table = 'tags';
			$sql = "SELECT DISTINCT tag FROM $table";
			
			try {
				$q = $conn -> prepare($sql);
				$q -> execute() or die("failed-execute");
			}
			catch (PDOException $e) {
				//Do your error handling here
				echo $e->getMessage();
			}
			
			$result = $q->fetchAll(PDO::FETCH_COLUMN, 0);
			
			file_put_contents($file_tags, json_encode($result));
		}
		
		
		//To upload list of existing tags
		if ($_GET['q'] == "tags_upload") {
			$id = $_GET['pmid'];
			$tags_list = $_GET['tags'];
			$tags = explode(',', $tags_list);
			
			$sql = "INSERT INTO tags (tag, pmid) VALUES (:tag_text, :pmid_value)";
			$q = $conn -> prepare($sql);
			$q->bindParam(':tag_text', $tag);
			$q->bindParam(':pmid_value', $id);
			
			foreach ($tags as $tag) {
				$q->execute();
			}
			
			
			$array = array($id, $tags_list);
			echo json_encode($array);
		}
		
	}
?>