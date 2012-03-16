<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>UNL_Geography_SpatialData_Campus</title>
</head>

<body>
<p>This file demonstrates the usage of the UNL_Geography_SpatialData_Campus package.</p>
<?php
ini_set('display_errors', true);
set_include_path(dirname(dirname(__FILE__)).'/src'.PATH_SEPARATOR.dirname(dirname(__FILE__)).'/lib/php');

function autoload($class)
{
    $class = str_replace('_', '/', $class);
    include $class . '.php';
}

spl_autoload_register("autoload");

if (isset($_GET['driver'])) {
    switch($_GET['driver']) {
        case 'sqlite':
            echo "<p>Using the SQLiteDriver</p>";
            UNL_Geography_SpatialData_Campus::$driver = new UNL_Geography_SpatialData_SQLiteDriver();
            break;
        case 'pdo':
            echo "<p>Using the PDOSQLiteDriver</p>";
            UNL_Geography_SpatialData_Campus::$driver = new UNL_Geography_SpatialData_PDOSQLiteDriver();
            break;
        case 'webservice':
            echo "<p>Using the UNLMapsWebServiceDriver</p>";
            UNL_Geography_SpatialData_Campus::$driver = new UNL_Geography_SpatialData_UNLMapsWebServiceDriver();
            break;
    }

}


$bldgs = new UNL_Common_Building();
$campus = new UNL_Geography_SpatialData_Campus();

foreach (array('NH','501') as $bldg_code) {
    $geoCoordinates = $campus->getGeoCoordinates($bldg_code);
    $polyCoordinates = $campus->getPolyCoordinates($bldg_code);
    echo "<p>The building, {$bldgs->codes[$bldg_code]} ($bldg_code) is located at lat:{$geoCoordinates['lat']} lon:{$geoCoordinates['lon']}</p>";
    echo '<ul>';
    echo "<li><a href='http://maps.google.com/?t=k&amp;ll={$geoCoordinates['lat']},{$geoCoordinates['lon']}&amp;spn=0.001212,0.002427&amp;om=1'>Google Map of this</a></li>";
    echo '</ul>';
    echo '<p>Its boundaries are:</p>';
    echo '<ul>';
    foreach ($polyCoordinates as $point) {
        echo "<li>{$point['lat']}, {$point['lon']}</li>";
    }
    echo '</ul>';
    echo '<a href="#" onclick="document.getElementById(\'source\').style.display=\'block\'; return false;">View Source+</a><div id="source" style="display:none;">'.highlight_file($_SERVER['SCRIPT_FILENAME'],true).'</div>';
}

?>
  <a href="spatialdata_example.php?driver=sqlite">Try the SQLite Driver</a>
| <a href="spatialdata_example.php?driver=webservice">Try the Web Service Driver</a>
| <a href="spatialdata_example.php?driver=pdo">Try the PDO Driver</a>
</body>
</html>