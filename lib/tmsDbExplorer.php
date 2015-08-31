<?php

class tmsDbExplorer
{

    /**
     * verbose mode
     * @var bool
     */
    protected $VERBOSE = true;

    /**
     * Path to database structure cache
     * @var string
     */
    protected $DB_STRUCTURE_CACHE = 'cache/db_struct.php';

    /**
     * path to models folder
     * @var string
     */
    protected $PATH_TO_MODELS = 'lib/models/';


    /**
     * save generated DB structure to cache or not
     * @var bool
     */
    protected $FLAG_SAVE_TABLES_CACHE = false;


    /**
     * base models prefix
     * @var string
     */
    protected $BASE_MODEL_PREFIX = '__base_model_';

    /**
     * @var \PDO
     */
    protected $dbh = null;

    /**
     * Tables with its structures
     * @var array
     */
    protected $TABLES = array();

    public function __construct()
    {
        $this->dbh = new PDO('mysql:host=localhost;dbname=bot', 'root', 'rjveybcn');
    }

    /**
     * Echo debug text line
     * @param string $text message text
     * @param bool $EOL is EOL needed or not
     */
    public function msgIfVerbose($text = '', $EOL = true)
    {
        if ($this->VERBOSE) {
            echo $text . ($EOL ? PHP_EOL : '');
        }
    }

    /**
     * get table list for database
     * @return bool
     */
    public function getTables()
    {
        $this->msgIfVerbose('Getting tables ... ', false);

        try {
            $q = $this->dbh->prepare("SHOW TABLES");
            $q->execute();
            $tmp = $q->fetchAll(PDO::FETCH_NUM);

            if (is_array($tmp) && count($tmp)) {
                foreach ($tmp as $row) {
                    $this->TABLES[$row[0]] = array();
                }
            }
            $this->msgIfVerbose('DONE.');
            return true;
        } catch (\Exception $e) {
            $this->msgIfVerbose('ERROR.');
            return false;
        }
    }

    /**
     * generate table structure gor selected table
     * @param null $table
     * @return bool
     */
    public function getTableStructure($table = null)
    {


        if (is_null($table)) {
            $this->msgIfVerbose('No tables');
            return false;
        }

        $this->msgIfVerbose("\t" . $table . ' ... ', false);

        try {
            $q = $this->dbh->prepare("SHOW COLUMNS FROM `$table`");
            $q->execute();
            $table_fields = $q->fetchAll(PDO::FETCH_ASSOC);

            $struct = array();
            if (is_array($table_fields) && count($table_fields)) {
                foreach ($table_fields as $field_info) {
                    if (isset($field_info['Field'])) {
                        $field_name = $field_info['Field'];
                        unset ($field_info['Field']);
                        $struct[$field_name] = $field_info;
                    }
                }
            }

            $this->TABLES[$table] = $struct;
            $this->msgIfVerbose('DONE.');

            return true;
        } catch (\Exception $e) {

        }
        $this->msgIfVerbose('ERROR.');
        return false;
    }

    /**
     * save database structure to cache
     * @return bool
     */
    public function saveTableStructure()
    {
        $this->msgIfVerbose('Saving structure to cache ... ', false);

        try {
            $data = '<?php' . PHP_EOL;
            $data .= '$tables = ' . var_export($this->TABLES, true).';';
            if (false === file_put_contents($this->DB_STRUCTURE_CACHE, $data)) {
                $this->msgIfVerbose('ERROR. Cann`t write to cache file.');
                return false;
            }
            $this->msgIfVerbose('DONE.');
            return true;
        } catch (\Exception $e) {
            $this->msgIfVerbose('ERROR.');
            return false;
        }
    }

    /**
     * update Database structure
     */
    public function updateDbScheme()
    {
        if (false === $this->getTables()) {
            return false;
        }
        $this->msgIfVerbose('Generating table structure:');

        foreach ($this->TABLES as $table_name => $table_structure) {
            if (false === $this->getTableStructure($table_name)) {
                return false;
            }
        }

        if ($this->FLAG_SAVE_TABLES_CACHE) {
            if (false === $this->saveTableStructure()) {
                return false;
            }
        }
        return true;
    }

    /**
     * build models for generated structure
     */
    public function buildModels()
    {
        $this->msgIfVerbose('Generating models:');

        foreach ($this->TABLES as $table_name => $structure) {
            $this->msgIfVerbose("\t" . $table_name . " base class ... ", false);

            $base_data = '<?php ' . PHP_EOL;


            $fields_init = '';
            $methods_init = '';


            foreach ($structure as $field => $field_info) {
                $fields_init .= "\tprotected $" . $field . " = NULL;" . PHP_EOL;

                $methods_init .= "\tpublic function get" . ucfirst($field) . "(){}" . PHP_EOL;
                $methods_init .= "\tpublic function set" . ucfirst($field) . "(\$value = null){}" . PHP_EOL;
            }

            $base_data .= 'class ' . $this->BASE_MODEL_PREFIX . $table_name . '{' . PHP_EOL;
            $base_data .= $fields_init . PHP_EOL;
            $base_data .= $methods_init . PHP_EOL;
            $base_data .= '}';


            if (false === file_put_contents($this->PATH_TO_MODELS . 'base/' . $this->BASE_MODEL_PREFIX . $table_name . '.php', $base_data)) {
                $this->msgIfVerbose('ERROR.');
            } else {
                $this->msgIfVerbose('DONE.');
            }


            $this->msgIfVerbose("\t" . $table_name . " class... ", false);

            $model_file = $this->PATH_TO_MODELS . $table_name . '.php';

            if (!file_exists($model_file) || !is_file($model_file)) {
                $content = '<?php ' . PHP_EOL;
                $content .= "class " . $table_name . ' extends ' . $this->BASE_MODEL_PREFIX . $table_name . "{" . PHP_EOL . PHP_EOL . "}";

                if (false === file_put_contents($model_file, $content)) {
                    $this->msgIfVerbose('ERROR. Cann`t write.');
                    return false;
                } else {
                    $this->msgIfVerbose('DONE.');

                }
            } else {
                $this->msgIfVerbose('Already exists. Unchanged.');
            }

        }
    }

    /**
     * read table structure from cache
     * @return bool
     */
    public function  loadDbStructure()
    {
        $this->msgIfVerbose('Reading table cache file ... ', false);

        if (!file_exists($this->DB_STRUCTURE_CACHE) || !is_file($this->DB_STRUCTURE_CACHE)) {
            $this->msgIfVerbose('ERROR. No cached table structure.');
            return false;
        }else{
            try{
                require_once $this->DB_STRUCTURE_CACHE;
                if(isset($tables)){
                    $this->TABLES = $tables;
                    $this->msgIfVerbose('DONE.');
                    return true;
                }else{
                    $this->msgIfVerbose('ERROR. table structure is empty.');
                    return false;
                }
            }catch (\Exception $e){
                $this->msgIfVerbose('ERROR. Cann`t read cached structure.');
                return false;
            }
        }
    }
}

