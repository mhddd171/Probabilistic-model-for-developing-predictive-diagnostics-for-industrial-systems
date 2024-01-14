<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_rows'])) {
    $conn = mysqli_connect("localhost", "root", "", "diagnoseSystem");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $observation_nodes = $_POST['delete_rows'];

    foreach ($observation_nodes as $observation_node) {
        $sql = "DELETE FROM scenarios WHERE observation = '$observation_node'";
        $conn->query($sql);
    }

    $conn->close();
}


header("Location: viewScenarioDB.php");
exit();
?>
