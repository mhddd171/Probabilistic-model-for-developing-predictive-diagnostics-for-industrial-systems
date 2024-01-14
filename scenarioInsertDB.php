<?php
// Database connection credentials
$servername = "localhost";
$username = "root";
$password = "";
$database = "diagnoseSystem";

// Establishing the database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Main Logic for POST method starts here

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $observation_node = $_POST["observation_node"];
    $faults = $_POST["fault_description"];
    $fault_probability_true = $_POST["fault_probability_true"];

    // Check if the new observation is already in the database
    $stmt = $conn->prepare("SELECT observation FROM scenarios WHERE observation = ?");
    $stmt->bind_param("s", $observation_node);
    $stmt->execute();
    $result = $stmt->get_result();

    $observationExists = $result->num_rows > 0;
    $stmt->close();

    // If the observation doesn't exist, insert it
    if (!$observationExists) {
        $stmt = $conn->prepare("INSERT INTO scenarios (observation, fault, fault_true_probability) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $observation_node, $fault, $fault_true_probability);

        for ($i = 0; $i < count($faults); $i++) {
            $fault = $faults[$i];
            $fault_true_probability = $fault_probability_true[$i];

            // Check if the fault already exists for the observation
            $checkStmt = $conn->prepare("SELECT 1 FROM scenarios WHERE observation = ? AND fault = ?");
            $checkStmt->bind_param("ss", $observation_node, $fault);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows == 0) {
                // If the fault doesn't exist for the observation, insert it
                if (!$stmt->execute()) {
                    echo "Error inserting fault: " . $stmt->error;
                }
            }
        }

        // Insert any missing faults with a probability of 0.1 for the new observation
        $sql = "SELECT DISTINCT fault FROM scenarios";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $existingFault = $row['fault'];

            if (!in_array($existingFault, $faults)) {
                $stmt = $conn->prepare("INSERT INTO scenarios (observation, fault, fault_true_probability) VALUES (?, ?, 0.1)");
                $stmt->bind_param("ss", $observation_node, $existingFault);

                if (!$stmt->execute()) {
                    echo "Error inserting fault for observation " . $observation_node . ": " . $stmt->error;
                }
            }
        }

        // Fetch all other observations from the database
        $sql = "SELECT DISTINCT observation FROM scenarios WHERE observation != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $observation_node);
        $stmt->execute();
        $result = $stmt->get_result();

        // For each other observation, insert any missing faults with a probability of 0.1
        while ($row = $result->fetch_assoc()) {
            $otherObservation = $row['observation'];

            foreach ($faults as $fault) {
                $stmt = $conn->prepare("INSERT INTO scenarios (observation, fault, fault_true_probability) VALUES (?, ?, 0.1)");
                $stmt->bind_param("ss", $otherObservation, $fault);

                if (!$stmt->execute()) {
                    echo "Error inserting fault for observation " . $otherObservation . ": " . $stmt->error;
                }
            }
        }

        include 'ScenarioInserted.html';

    } else {
        // Check if any of the faults for the observation already exist
        $stmt = $conn->prepare("SELECT fault FROM scenarios WHERE observation = ? AND fault IN (" . implode(',', array_fill(0, count($faults), '?')) . ")");
        $stmt->bind_param(str_repeat('s', count($faults) + 1), $observation_node, ...$faults);
        $stmt->execute();
        $existingFaultsResult = $stmt->get_result();

        $existingFaults = [];
        while ($row = $existingFaultsResult->fetch_assoc()) {
            $existingFaults[] = $row['fault'];
        }

        foreach ($faults as $fault) {
            if (!in_array($fault, $existingFaults)) {
                $stmt = $conn->prepare("INSERT INTO scenarios (observation, fault, fault_true_probability) VALUES (?, ?, ?)");
                $stmt->bind_param("ssd", $observation_node, $fault, $fault_probability_true[array_search($fault, $faults)]);

                if (!$stmt->execute()) {
                    echo "Error inserting fault: " . $stmt->error;
                }
            }
        }

        // ... [Rest of your code for inserting the faults for other observations]

        include 'duplicatedScenario.html';
    }
}

// Delete rows based on the criteria
$deleteSql = "
DELETE FROM scenarios
WHERE (observation, fault) IN (
    SELECT b.observation, b.fault 
    FROM (
        SELECT observation, fault, COUNT(*) 
        FROM scenarios 
        GROUP BY observation, fault 
        HAVING COUNT(*) > 1
    ) b
)
AND fault_true_probability = 0.1

";

if ($conn->query($deleteSql) === TRUE) {
    
} else {
    echo "Error deleting records: " . $conn->error;
}

$conn->close();
?>
