<?php
$servername = "localhost";
$username = "root";
$password = "";
$DB = "diagnoseSystem";

$connect = new mysqli($servername, $username, $password, $DB);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

$sql = "SELECT DISTINCT observation FROM scenarios";
$result = $connect->query($sql);

$observation_nodes = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $observation_nodes[] = $row['observation'];
    }
}

$connect->close();
?>

<html>
<head>
    <title>Diagnosis Interface</title>
    <style type="text/css">
        body { font-family: Calibri, sans-serif; color: #E6E3E9; background-color: #C6B5D6; margin: 0; padding: 0;
        }

        label { display: inline-block; width: 190px; text-align: left; margin-bottom: 10px;
        }

        input[type="checkbox"] { background-color: rgba(0, 0, 0, 0.5); border: none; width: 15px; height: 15px; color: #E6E3E9; border-radius: 9px;
        }

        .container { background-color: #333; width: 600px; border-radius: 20px; padding: 20px; margin: 50px auto;
        }
    </style>
</head>
<body>
    <div style="position: fixed; left: 0; top: 0; border-top: 0; z-index: 100; background-color:#333; width: 100%; border-bottom: solid white 3px; height: 75px">
        <center>
            <font face="Calibri" color="#E6E3E9" style="padding: 7px 15px; display: inline-block; font-size: 43px;">DIAGNOSIS</font>
        </center>
    </div>
    <br><br><br><br>
    <div class="container">
        <center>
            <p style="font-size: 25px;">PLEASE SELESCT THE ISSUES WITH THE CAR</p>
            <form action="http://localhost/root/diagnose3.php" method="post">
                <?php
                foreach ($observation_nodes as $observation) {
                    echo '<label for="' . $observation . '">' . $observation . ':</label>';
                    echo '<input type="checkbox" id="' . $observation . '" name="symptoms[]" value="' . $observation . '"><br>';
                }
                ?>
                <br><input type="submit" name="submit" value="DIAGNOSE" style="background-color: #ECB1D1; border:none; border-radius: 20px; color:#333; width: 100px; height: 25px; font-size: 15px; left: 75px; margin: 5px;">
                <br>
                <a href="http://localhost/root/main page.html"><input type="button" value="MAIN" style="background-color: #ECB1D1; border:none; border-radius: 20px; color:#333; width: 100px; height: 25px; font-size: 15px; left: 75px;"></a>
            </form>
        </center>
    </div>
</body>
</html>
