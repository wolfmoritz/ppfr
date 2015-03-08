<?php
namespace Recipe\Storage;

use \PDO;

/**
 * Abstract Data Mapper Class
 *
 * All domain data mapper classes extend this class
 */
abstract class DataMapperAbstract 
{
  // ------------------------------------------
  // Define these properties in the child class
  // ------------------------------------------

  /**
   * Table Name
   * @var String
   */
  protected $table;

  /**
   * Table Alias
   * @var String
   */
  protected $tableAlias;

  /**
   * Primary Key Column Name
   * Define if not 'id'
   * @var String
   */
  protected $primaryKey = 'id';

  /**
   * Updatable/Insertable Columns, not including the who columns
   * @var Array
   */
  protected $modifyColumns = array();

  /**
   * Domain Object Class
   * @var String
   */
  protected $domainObjectClass;

  /**
   * Default select statement from 'select' to before 'where'
   * @var String
   */
  protected $defaultSelect;

  /**
   * Does this table have created_by, created_date, updated_by, and updated_date?
   * @var Boolean
   */
  protected $who = true;

  // ------------------------------------------
  // Do not define properties below
  // ------------------------------------------

  /**
   * Database PDO Connection Object
   *
   * When the constructor is called the PDO connection handler is assigned once
   * When child objects are instantiated they will inherit the same PDO connection
   * @var PDO Object, Database Connection Handle
   */
  protected static $dbh;

  /**
   * Session Object
   * @var SessionHandler Object
   */
  protected static $session;

  /**
  * Session User ID
  * @var Int
  */
  protected $sessionUserId = 1;
  
  /**
   * Application Object
   * @var Application Object
   */
  protected static $logger;

  /**
   * SQL Statement to Execute
   * @var String
   */
  protected $sql;

  /**
   * Bind Values
   * @var Array
   */
  protected $bindValues = array();

  /**
   * PDO Statement Being Executed
   * @var PDO Prepared Statement Object
   */
  protected $statement;

  /**
   * Construct
   *
   * @param $pdo PDO Connection
   * @param $session SessionHandler
   * @param $logger Logging
   */
  public function __construct($pdo, $session, $logger)
  {
    if (!self::$dbh) {
      self::$dbh = $pdo;
    }

    // TODO Fix this
    if (!self::$session) {
      self::$session = $session;
      $userId = self::$session->getData('user_id');
      $this->sessionUserId = ($userId) ? $userId : 1;
    }

    if (!self::$logger) {
      self::$logger = $logger;
    }
  }

  /**
   * Create a new Domain Object
   *
   * Uses the $domainObjectClass defined in the child class
   * @return Object
   */
  public function make()
  {
    $fullyQualifedClassName = __NAMESPACE__ . '\\' . $this->domainObjectClass;

    return new $fullyQualifedClassName;
  }

  /**
   * Get one table row by the primary key ID
   *
   * @param $id, Numeric primary key ID
   * @return mixed, Domain Object if found, null otherwise
   */
  public function findById($id) 
  {
    // Verify that a numeric key was supplied
    if(is_numeric($id)) {

      // Use default select statement and add where clause, unless other SQL has been supplied
      if (empty($this->sql)) {
        $this->sql = $this->defaultSelect . ' where ' . (($this->tableAlias) ? $this->tableAlias : $this->table) . '.' . $this->primaryKey . ' = ?';
      }

      $this->bindValues[] = (int) $id;

      // Execute the query
      $this->execute();
      $result = $this->statement->fetch();
      $this->clear();
      
      return $result;
    }

    return null;
  }

  /**
   * Get all table rows
   *
   * Returns an array of Domain Objects (one for each record)
   * @return Array
   */
  public function find()
  {
    // Use default select statement unless other SQL has been supplied
    if (empty($this->sql)) {
      $this->sql = $this->defaultSelect;
    }

    // Execute the query
    $this->execute();
    $data = $this->statement->fetchAll();
    $this->clear();

    return $data;
  }

  /**
   * Save Domain Object
   *
   * Inserts or updates Domain Object record
   * @param Domain Object
   * @return mixed, Domain Object on success, false otherwise
   */
  public function save(DomainObjectAbstract $domainObject) 
  {
    if(is_numeric($domainObject->{$this->primaryKey})) {
      return $this->update($domainObject);
    } else {
      return $this->insert($domainObject);
    }
  }
  
  /**
   * Update a Record (Public)
   * 
   * Define in child class to add any manipulation before _update()
   * @param Domain Object
   * @return Domain Object
   */
  public function update(DomainObjectAbstract $domainObject)
  {
    return $this->_update($domainObject);
  }

  /**
   * Insert a Record (Public)
   * 
   * Define in child class to add any manipulation before _insert()
   * @param Domain Object
   * @return Domain Object
   */
  public function insert(DomainObjectAbstract $domainObject)
  {
    return $this->_insert($domainObject);
  }
  
  /**
   * Delete a Record (Public)
   * 
   * Define in child class to override behavior
   * @param Domain Object
   * @return Boolean
   */
  public function delete(DomainObjectAbstract $domainObject)
  {
    return $this->_delete($domainObject);
  }
  
  /**
   * Clear Prior SQL Statement
   * @return void
   */
  public function clear() 
  {
    $this->sql = null;
    $this->bindValues = array();
    //$this->statement = null;
  }

  /**
   * Current Date Time in MySQL Format
   * @return String
   */
  public function now()
  {
    return date('Y-m-d H:i:s');
  }
 
  // ------------------------------------------
  // Protected Methods
  // ------------------------------------------

  /**
   * Update a Record (Protected)
   *
   * Updates a single record using the primarky key ID
   * @param Domain Object
   * @return Domain Object
   */
  protected function _update(DomainObjectAbstract $domainObject)
  {
    // Make sure a primary key was set
    if (!is_numeric($domainObject->{$this->primaryKey})) {
      throw new \Exception('A primary key id was not provided to update the record.');
    }

    // Get started
    $this->sql = 'update ' . $this->table . ' set ';

    // Use set object properties which match the list of updatable columns
    $hasBeenSet = 0;
    foreach($this->modifyColumns as $column) {
      if (isset($domainObject->$column)) {
        $this->sql .= $column . ' = ?, ';
        $this->bindValues[] = $domainObject->$column;
        $hasBeenSet++;
      }
    }

    // Is there anything to actually update?
    if ($hasBeenSet === 0) {
      // No, log and return
      self::$logger->debug('Nothing to update');      
      return null;
    }

    // Remove last comma at end of SQL string
    $this->sql = rtrim($this->sql, ', ');
    
    // Set Who columns
    if ($this->who) {
      $this->sql .= ', updated_by = ?, updated_date = ? ';
      $this->bindValues[] = $this->sessionUserId;
      $this->bindValues[] = $this->now();
    }

    // Append where clause
    $this->sql .= ' where ' . $this->primaryKey . ' = ?;';
    $this->bindValues[] = $domainObject->{$this->primaryKey};

    // Execute
    $this->execute();
    $this->clear();
    
    return $domainObject;
  }

  /**
   * Insert a New Record (Protected)
   *
   * @param Domain Object
   * @return Domain Object
   */
  protected function _insert(DomainObjectAbstract $domainObject) 
  {
    // Get started
    $this->sql = 'insert into ' . $this->table . ' (';

    // Insert values placeholder string
    $insertValues = ' ';

    $hasBeenSet = 0;
    foreach($this->modifyColumns as $column) {
      if (isset($domainObject->$column)) {
        $this->sql .= $column . ', ';
        $insertValues .= '?, ';
        $this->bindValues[] = $domainObject->$column;
        $hasBeenSet++;
      }
    }

    // Is there anything to actually insert?
    if ($hasBeenSet === 0) {
      // No, log and return
      self::$logger->debug('Nothing to insert');      
      return null;
    }
    
    // Remove trailing commas
    $this->sql = rtrim($this->sql,', ');
    $insertValues = rtrim($insertValues,', ');

    // Set Who columns
    if ($this->who) {
      // Append statement
      $this->sql .= ', created_by, created_date, updated_by, updated_date';
      $insertValues .= ', ?, ?, ?, ?';

      // Add binds
      $this->bindValues[] = $this->sessionUserId;
      $this->bindValues[] = $this->now();
      $this->bindValues[] = $this->sessionUserId;
      $this->bindValues[] = $this->now();      
    }

    // Close and concatenate strings
    $this->sql .= ') values (' . $insertValues . ');';

    // Execute and assign last insert ID to primary key and return
    $this->execute();
    $domainObject->{$this->primaryKey} = self::$dbh->lastInsertId();
    $this->clear();

    return $domainObject;
  }

  /**
   * Delete a Record (Protected)
   *
   * @param Domain Object
   * @return Boolean
   */
  protected function _delete(DomainObjectAbstract $domainObject)
  {
    // Make sure the ID was set
    if (!is_numeric($domainObject->{$this->primaryKey})) {
      throw new \Exception('A primary key id was not provided to delete this record.');
    }

    // Make SQL Statement
    $this->sql = 'delete from ' . $this->table . ' where ' . $this->primaryKey . ' = ?;';
    $this->bindValues[] = $domainObject->{$this->primaryKey};

    // Execute
    $this->execute();
    $this->clear();
    
    return;
  }

  /**
   * Execute SQL
   *
   * Executes $this->sql string using $this->bindValues array
   * Returns true/false for DML, and query result array for selects
   * @return mixed
   */
  protected function execute()
  {
    // Log query and binds
    self::$logger->debug('SQL Statement: ' . $this->sql);
    self::$logger->debug('SQL Binds: ' . print_r($this->bindValues,true));

    // Prepare the query
    $this->statement = self::$dbh->prepare($this->sql);

    // Bind values
    // TODO Does this use integers if supplied?
    foreach ($this->bindValues as $key => $value) {
      // Determine data type
      if(is_int($value)) {
        $paramType = PDO::PARAM_INT;
      } elseif ($value === '') {
        $value = null;
        $paramType = PDO::PARAM_NULL;
      } else {
        $paramType = PDO::PARAM_STR;
      }

      $this->statement->bindValue($key + 1, $value, $paramType);
    }

    // Execute the query
    if (false === $outcome = $this->statement->execute()) {
      // If false is returned there was a problem so log it
      self::$logger->error('PDO Execute Returns False: ' . $this->sql);
      self::$logger->error('PDO SQL Binds: ' . print_r($this->bindValues,true));

      return null;
    }

    // If a select statement was executed, set fetch mode
    if (stristr($this->sql, 'select')) {
      $this->statement->setFetchMode(PDO::FETCH_CLASS, __NAMESPACE__ . '\\' . $this->domainObjectClass);
    }
    
    return $outcome;
  }
}
