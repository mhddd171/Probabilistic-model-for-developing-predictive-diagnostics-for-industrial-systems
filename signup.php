<?php
	$ID = $_POST['ID'];
	$name = $_POST['name'];
	$specialty = $_POST['specialty'];
	$password = $_POST['password'];

	if (!empty($ID) || !empty($name) || !empty($specialty) || !empty($password)) {
		$host = "localhost";
		$dbUsername = "root";
		$dbPassword = "";
		$dbname = "diagnoseSystem";

		
		$conn = new mysqli($host, $dbUsername, $dbPassword, $dbname);

		if (mysqli_connect_error()) {
			die('Connect Error('.mysqli_connect_error().')'.mysqli_connect_error());

		}else{
			$SELECT = "SELECT ID FROM signup Where ID = ? Limit 1";
			$INSERT= "INSERT Into signup (ID, name, specialty, password) values (?, ?, ?, ?)";

			//Prepare statement
			$stmt= $conn->prepare($SELECT);
			$stmt->bind_param("s", $ID);
			$stmt->execute();
			$stmt->bind_result($ID);
			$stmt->store_result();
			$rnum = $stmt->num_rows;

			if ($rnum==0) {
				$stmt->close();

				$stmt = $conn->prepare($INSERT);
				$stmt->bind_param("ssss", $ID, $name, $specialty, $password);
				$stmt->execute();
				include 'expert.php';

			}else{
				include 'duplicatedID.html';
			}
			$stmt->close();
			$conn->close();
		}
	} else{
		echo "All field are required";
		die();
	}
?>
