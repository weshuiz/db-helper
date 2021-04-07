<?php
declare(strict_types= 1);

// convert size units
function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

/*
this class can connect to sql and execute query's in a safe manner
using prepared statements

to connect to a database

EXAMPPLE:

    $database = new Database(); //make connection to Databae
    $connection = $database->connect()


to run a query make sure you use propper place holders
the array paramater fills those place holders in the same order

EXAMPLE:

    $query = "SELECT id,name,email,password FROM users WHERE email = (?) AND name = (?);";
    $results = $database->query($query, [$email,$firstname]);

query's that do not fetch from a database will return a
    status code,
    message,
    time stamp

instead
*/

class Database
{
    //Connect to the database
    public function connect()
    {
        $host = $_ENV["DB_HOS"];
        $username = $_ENV["DB_USER"];
        $password = $_ENV["DB_PASSWORD"];
        $database = $_ENV["DB_NAME"];
        $charset = $_ENV["DB_CHARSET"];
        $method = "mysql";

        # PDO statement
        $dsn = "$method:host=$host;dbname=$database;charset=$charset";
        $options = [// PDO attribute settings
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        # attempt connection to Database
            try {
                $this->connection = new PDO($dsn,$username,$password, $options);
                return $this->connection; // returns the connection if succsessfull
            }
            catch(PDOException $error)// failed connecting to database
            {
                die("Database connection failed: ". $error->getMessage() );
            } 
    }

    function query(string $query, array $values = null)
    {
        // get the first argument from the query string
        $action = explode(' ',trim($query));
        $action = $action[0];
        $time_start = null;
        $time_end = null;
        $mem_usage = null;
        $mem_peak = null;
        $duration = null;
        $usage = null;
        $statystics = null;
        //check if the query is a 'select' (case insensetive)
        if(strcasecmp("select", $action) == 0)
        {
            try {// fetch query
                $date = date("Y-m-d");
                $time_start = hrtime(true);
                $statement = $this->dbConnection()->prepare($query); //prepared statement
                $statement->execute($values); //fill the place holders and execute
                $results = $statement->fetchAll(PDO::FETCH_ASSOC); //return the feteched result
                $time_end = hrtime(true);
                $mem_usage = convert(memory_get_usage(true));
                $mem_peak = convert(memory_get_peak_usage(true));
                $duration = $time_end - $time_start; //calculates total time taken
                syslog(LOG_DEBUG, "$date query: SELECT time: $duration memory: $mem_peak");
                $row = $statement->rowCount();
                if($row > 1) {
                    http_response_code(200);
                    $statystics = ["status" => 200, "message" => "ok","timestamp" => time()];    
                }else {
                    http_response_code(404);
                    $statystics = ["status" => 404, "message" => "not found","timestamp" => time()];
                }
                //return $results;
                return ["data" => $results, "request" => $statystics];
            } catch (Throwable $err) {// there was a error while fetching
                throw new Exception("failed to get data", $err);    
            }
        }else {// not a select query (Execute instead)
            try {//try executing the query
                $time_start = hrtime(true);
                $statement = $this->dbConnection()->prepare($query);
                $statement->execute($values);
                $time_end = hrtime(true);
                $mem_usage = convert(memory_get_usage(true));
                $mem_peak = convert(memory_get_peak_usage(true));
                $duration = $time_end - $time_start; //calculates total time taken
                syslog(LOG_DEBUG, "$date query: ACTION time: $duration memory: $mem_peak");
                $row = $statement->rowCount();
                if($row == 1) {
                    http_response_code(200);
                    $statement->execute($values);
                    return $statystics = ["status" => 200, "message" => "done","timestamp" => time()];
                }else {
                    http_response_code(404);
                    //$statement->execute($values)
                    return $statystics = ["status" => 404, "message" => "not found","timestamp" => time()];
                }
            } catch (Exception $err) {// failed to execute query something whent wrong
                throw new Exception("server error", $err);
            }
        }
    }
}