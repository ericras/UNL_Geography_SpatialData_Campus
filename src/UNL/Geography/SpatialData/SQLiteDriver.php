<?php

class UNL_Geography_SpatialData_SQLiteDriver implements UNL_Geography_SpatialData_DriverInterface
{
    static public $db_file = 'spatialdata.sqlite';

    protected $db;

    public $bldgs;

    protected $db_class = 'SQLiteDatabase';

    function __construct()
    {
        $this->bldgs = new UNL_Common_Building();
    }

    /**
     * Returns the geographical coordinates for a building.
     *
     * @param string $code Building Code for the building you want coordinates of.
     * @return Associative array of coordinates lat and lon. false on error.
     */
    function getGeoCoordinates($code)
    {
        $this->_checkDB();
        if ($result = $this->getDB()->query('SELECT lat,lon FROM campus_spatialdata WHERE code = \''.$code.'\';')) {
            while ($coords = $result->fetch()) {
                return array('lat'=>$coords['lat'],
                             'lon'=>$coords['lon']);
            }
        }
        return false;
    }

    /**
     * Returns the geographical boundary coordinates for a building.
     *
     * @param string $code Building Code for the building you want coordinates of.
     * @return Array of associative arrays of coordinates lat and lon. false on error.
     */
    function getPolyCoordinates($code)
    {
        $this->_checkDB();
        if ($result = $this->getDB()->query('SELECT lat,lon FROM campus_spatialdata_poly WHERE code = \''.$code.'\';')) {
            while ($coords = $result->fetch()) {
                $data[] = array('lat'=>$coords['lat'],
                                'lon'=>$coords['lon']);
            }
            return (isset($data) ? $data : array());
        }
        return false;
    }

    /**
     * Checks if a building with the given code exists.
     *
     * @param string Building code.
     * @return bool true|false
     */
    function buildingExists($code)
    {
        if (isset($this->bldgs->codes[$code])) {
            return true;
        }
        return false;
    }

    protected function _checkDB()
    {
        if (!$this->tableExists('campus_spatialdata')) {
            $this->getDB()->query(self::getTableDefinition('campus_spatialdata'));
            $this->importCSV('campus_spatialdata', self::getDataDir().'campus_spatialdata.csv');
        }
        if (!$this->tableExists('campus_spatialdata_poly')) {
            $this->getDB()->query(self::getTableDefinition('campus_spatialdata_poly'));
            $this->importCSV('campus_spatialdata_poly', self::getDataDir().'campus_spatialdata_poly.csv');
        }
    }

    static public function getTableDefinition($table)
    {
        switch ($table) {
            case 'campus_spatialdata':
                return "CREATE TABLE campus_spatialdata (
                          id int(11) NOT NULL,
                          code varchar(10) NOT NULL default '',
                          lat float(16,14) NOT NULL default '0.00000000000000',
                          lon float(16,14) NOT NULL default '0.00000000000000',
                          PRIMARY KEY  (id),
                          UNIQUE (code)
                        ) ; ";
                break;
            case 'campus_spatialdata_poly':
                return "CREATE TABLE campus_spatialdata_poly (
                          id int(11) NOT NULL,
                          code varchar(10) NOT NULL default '',
                          lat float(16,14) NOT NULL default '0.00000000000000',
                          lon float(16,14) NOT NULL default '0.00000000000000',
                          PRIMARY KEY  (id),
                          UNIQUE (code, lat, lon)
                        ) ; ";
                break;
        }
    }

    function getDB()
    {
        if (!isset($this->db)) {
            return $this->__connect();
        }
        return $this->db;
    }

    function tableExists($table)
    {
        $db = $this->getDB();
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetch();
        return $result > 0;
    }

    protected function __connect()
    {
        if ($this->db = new $this->db_class(self::getDataDir().self::$db_file)) {
            return $this->db;
        }
        throw new Exception('Cannot connect to database!');
    }

    public function importCSV($table, $filename)
    {
        $db = $this->getDB();
        if ($h = fopen($filename,'r')) {
            switch ($table) {
                case 'campus_spatialdata':
                    while ($line = fgets($h)) {
                        $data = array();
                        $line = str_replace('NULL', '""', $line);
                        foreach (explode('","',$line) as $field) {
                            $data[] = "'".sqlite_escape_string(stripslashes(trim($field, "\"\n")))."'";
                        }
                        $data = implode(',',$data);
                        $db->query("INSERT INTO ".$table." VALUES ($data);");
                    }
                    break;
                case 'campus_spatialdata_poly':
                    $id = 1;
                    while ($line = fgets($h)) {
                        $data = array();
                        $line = str_replace('NULL', '""', $line);
                        foreach (explode('","',$line) as $key => $field) {
                            if ($key == 0) {
                                $code = $field;
                            } else {
                                $data[] = trim($field, "\"\n");
                            }
                        }
                        foreach ($data as $latlon) {
                            $coords = array();
                            foreach (explode(' ',$latlon) as $field) {
                                $coords[] = "'".sqlite_escape_string(stripslashes($field))."'";;
                            }
                            $data = "'".$id."','".sqlite_escape_string(stripslashes(trim($code, "\"\n")))."',".implode(',',$coords);
                            $id++;
                            $db->query("INSERT INTO ".$table." VALUES ($data);");
                        }
                    }
                    break;
            }
        }
    }

    static public function getDataDir()
    {
        if (file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/pear.unl.edu/UNL_Geography_SpatialData_Campus')) {
            return dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/pear.unl.edu/UNL_Geography_SpatialData_Campus/';
        }
        if (file_exists(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/UNL_Geography_SpatialData_Campus')) {
            return dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/UNL_Geography_SpatialData_Campus/data/';
        }
        return dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/data/';
    }
}
