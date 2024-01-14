<?php
$servername = "localhost";
$username = "root";
$password = "";
$DB = "diagnoseSystem";

$connect = new mysqli($servername, $username, $password, $DB);

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

$distinctFaults = [];
$distinctObservations = [];
$probability_of_observation_given_fault_table = [];

$sql = "SELECT DISTINCT fault FROM scenarios";
$result = $connect->query($sql);
$i = 0;
while ($row = $result->fetch_assoc()) {
    $distinctFaults[$row['fault']] = $i;
    $i++;
}

$sql = "SELECT DISTINCT observation FROM scenarios";
$result = $connect->query($sql);
$i = 0;
while ($row = $result->fetch_assoc()) {
    $distinctObservations[$row['observation']] = $i;
    $i++;
}

foreach ($distinctObservations as $observation => $obsIndex) {
    $probabilities = [];
    foreach ($distinctFaults as $fault => $faultIndex) {
        $sql = "SELECT fault_true_probability FROM scenarios WHERE observation = ? AND fault = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ss", $observation, $fault);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $probabilities[] = $row['fault_true_probability'];
        } else {
            $probabilities[] = null;
        }
    }
    $probability_of_observation_given_fault_table[] = $probabilities;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedSymptoms = isset($_POST['symptoms']) ? $_POST['symptoms'] : [];
}

$prior = 1 / count($distinctFaults);
$faults_likelihood = array_fill_keys(array_keys($distinctFaults), $prior);
$normalization_constant = 0;

foreach ($distinctFaults as $fault => $Faultindex) {
    $all_evidence = 1;
    foreach ($distinctObservations as $observation => $Obsindex) {
        $evidence = $probability_of_observation_given_fault_table[$Obsindex][$Faultindex] ?? null;
        if ($evidence !== null) {
            if (!in_array($observation, $selectedSymptoms)) {
                $evidence = 1 - $evidence;
            }
            $all_evidence *= $evidence;
        }
    }
    if (count($selectedSymptoms) > 0) {
        $faults_likelihood[$fault] *= $all_evidence;
    }
    $normalization_constant += $faults_likelihood[$fault];
}

arsort($faults_likelihood);
$topFourFaults = array_slice($faults_likelihood, 0, 4);

$currentDateTime = date('Y-m-d H:i:s'); 

$currentDateTime = date('Y-m-d H:i:s'); 

foreach ($topFourFaults as $fault => $likelihood) {
    $probability = ($likelihood / $normalization_constant) * 100;
    
    
    $selectedSymptomsString = implode(", ", $selectedSymptoms);

    
    $stmt = $connect->prepare("INSERT INTO online_diagnose_history (observation, fault, probability, date_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $selectedSymptomsString, $fault, $probability, $currentDateTime);

    if (!$stmt->execute()) {
        echo "Error inserting record: " . $stmt->error;
    }
}

$connect->close();
?>

<html>
<head>
    <title>Diagnosis</title>
    <style type="text/css">
        body {
            font-family: Calibri, sans-serif;
            color: #E6E3E9;
            background-color: #C6B5D6;
            margin: 0;
            padding: 0;
        }
        label {
            display: inline-block;
            width: 190px;
            text-align: left;
            margin-bottom: 10px;
        }
        .container {
            background-color: #333;
            width: 600px;
            border-radius: 20px;
            padding: 20px;
            margin: 50px auto;
        }
        table {
            width: 80%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #E6E3E9;
            color: #333;
        }
        h2 {
            margin-bottom: 1px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <h2>Probability of Each Fault</h2>
        <hr style="width: 300px;">
        <table>
            <thead>
                <tr>
                    <th>Fault</th>
                    <th>Probability</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topFourFaults as $fault => $likelihood): ?>
                    <tr>
                        <td><?php echo $fault; ?></td>
                        <td><?php echo number_format(($likelihood / $normalization_constant) * 100, 2); ?> %</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <canvas id="probabilityChart" width="600" height="400" style="background-color: rgba(0, 0, 0, 0.2); border-radius:15px;"></canvas>
        <br>
        <a href="http://localhost/root/diagnose3interface.php">
            <input type="button" value="BACK" style="background-color: #ECB1D1; border:none; border-radius: 20px; color:#333; width: 100px; height: 25px; font-size: 15px; left: 75px;">
        </a>
    </center>
</div>
<script>
    var ctx = document.getElementById('probabilityChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($topFourFaults)); ?>,
            datasets: [{
                label: 'Probability',
                data: <?php echo json_encode(array_values($topFourFaults)); ?>.map(value => (value / <?php echo $normalization_constant; ?>) * 100),
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return value.toFixed(2) + ' %';
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>
