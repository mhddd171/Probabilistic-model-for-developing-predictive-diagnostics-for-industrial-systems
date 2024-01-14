<!DOCTYPE html>
<html>

<head>
    <title>Online Diagnosis History</title>
    <style type="text/css">
        textarea {
            background-color: rgba(0, 0, 0, 0.5);
            border: none;
            color: #E6E3E9;
            border-radius: 7px;
        }

        td {
            text-align: center;
        }

        th {
            font-size: 25px;
        }
    </style>
</head>

<body text="#E6E3E9" bgcolor="#C6B5D6">
    <div style="position: fixed; left: 0; top: 0; border-top: 0; z-index: 100; background-color:#333; width: 100%; border-bottom: solid white 3px; height: 75px">
        <center><font face="Calibri" color="#E6E3E9" style="padding: 7px 15px; display: inline-block; font-size: 43px;">Online Diagnosis History</font></center>
    </div><br><br><br><br>
    <font face="calibri">
        <center><br>
            <div style="background-color: #333; width: 1000px; border-radius: 20px;"><br><br>
                <form action="deleteHistory.php" method="post">
                    <table border="2px" style="border-color: black; font-size: 17px; font-family: calibri; background-color: rgba(0, 0, 0, 0.3);" align="center" cellpadding="20px" cellspacing="0" width="95%">

                        <div>
                            <?php
                            $conn = mysqli_connect("localhost", "root", "", "diagnoseSystem");
                            if (!$conn) {
                                die("Connection failed: " . mysqli_connect_error());
                            }

                            $sql = "SELECT DISTINCT observation FROM online_diagnose_history";
                            $result_observation = $conn->query($sql);

                            if ($result_observation->num_rows > 0) {
                                echo "<tr><th>Selected</th><th>Fault</th><th>Probability</th><th>Date and Time</th><th>Delete</th></tr>";
                                while ($row_observation = $result_observation->fetch_assoc()) {
                                    $observation_node = $row_observation["observation"];

                                    // Fetch data for the current observation_node using prepared statement
                                    $sql_data = "SELECT fault, probability, DATE_FORMAT(date_time, '%Y-%m-%d %h:%i %p') as formatted_date_time FROM online_diagnose_history WHERE observation = ?";
                                    $stmt = $conn->prepare($sql_data);
                                    $stmt->bind_param("s", $observation_node);
                                    $stmt->execute();
                                    $result_data = $stmt->get_result();

                                    if ($result_data->num_rows > 0) {
                                        $row_data = $result_data->fetch_assoc();

                                        // Output a single row for the observation_node
                                        echo "<tr>
                                                <td rowspan='" . $result_data->num_rows . "'>" . $observation_node . "</td>
                                                <td>" . $row_data["fault"] . "</td>
                                                <td>" . number_format($row_data["probability"], 2) . "%</td>
                                                <td>" . $row_data["formatted_date_time"] . "</td>
                                                <td rowspan='" . $result_data->num_rows . "'><input type='checkbox' name='delete_rows[]' value='" . $observation_node . "'></td>
                                              </tr>";

                                        // Output additional rows for the same observation_node
                                        while ($row_data = $result_data->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>" . $row_data["fault"] . "</td>
                                                    <td>" . number_format($row_data["probability"], 2) . "%</td>
                                                    <td>" . $row_data["formatted_date_time"] . "</td>
                                                  </tr>";
                                        }
                                    }
                                }
                                echo "</table>";
                                echo "<br><input type='submit' style='background-color: #ECB1D1; border:none; border-radius: 20px; color:#333; width: 200px; height: 25px; font-size: 15px; left: 75px;' value='Delete Selected Rows'>";
                            } else {
                                echo "0 results";
                            }
                            $conn->close();
                            ?><br><br><a href="expertServices.php"><input type="button" value="BACK" style="background-color: #ECB1D1; border:none; border-radius: 20px; margin: 12px; color:#333; width: 100px; height: 25px; font-size: 15px; left: 75px; "></a>
                        </div>
                    </table>
                </form>
                <br><Br><br></div>
            </div></center>
</body>

</html>
