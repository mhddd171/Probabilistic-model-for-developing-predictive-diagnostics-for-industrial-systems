<?php
$connect=mysqli_connect("localhost", "root", "","diagnoseSystem");
if (!empty($_POST['submit'])) {
	$ID=$_POST['ID'];
	$password=$_POST['password'];
	$query="select * from signup where ID='$ID' and password='$password'";
	$result=mysqli_query($connect,$query);
	$count=mysqli_num_rows($result);
	if ($count>0) {
		include 'expertServices.php';
	}else{
		include 'loginfaild.html';
	}
}
?>
