<?php
	/**
	* Database schema abstraction class for MySQL
	* @author Michael Dean <mdean@users.sourceforge.net>
	* @author Miles Lott <milosch@phpgroupware.org>
	* @copyright Copyright (C) ? Michael Dean, Miles Lott
	* @copyright Portions Copyright (C) 2003,2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/gpl.html GNU General Public License
	* @package phpgwapi
	* @subpackage database
	* @version $Id$
	*/
	namespace App\modules\phpgwapi\services\SchemaProc;
	use PDO;

	/**
	* Database schema abstraction class for MySQL
	*
	* @package phpgwapi
	* @subpackage database
	*/
	class SchemaProcMysql
	{
		var $m_sStatementTerminator;
		var $check_contstaints = array();

		/* Following added to convert sql to array */
		var $sCol = array();
		var $pk = array();
		var $fk = array();
		var $ix = array();
		var $uc = array();
		var $indexes_sql = array();

		function __construct()
		{
			$this->m_sStatementTerminator = ';';

		}

		/* Return a type suitable for DDL */
		function TranslateType($sType, $iPrecision = 0, $iScale = 0)
		{
			$sTranslated = '';
			switch($sType)
			{
				case 'auto':
					$sTranslated = 'int(11) auto_increment';
					break;
				case 'bool':
					$sTranslated = 'bool';
					break;
				case 'blob':
					$sTranslated = 'blob';
					break;
				case 'char':
					if ($iPrecision > 0 && $iPrecision < 256)
					{
						$sTranslated =  sprintf("char(%d)", $iPrecision);
					}
					if ($iPrecision > 255)
					{
						$sTranslated =  'text';
					}
					break;
				case 'date':
					$sTranslated =  'date';
					break;
				case 'datetime':
					$sTranslated =  'datetime';
					break;
				case 'decimal':
					$sTranslated =  sprintf("decimal(%d,%d)", $iPrecision, $iScale);
					break;
				case 'float':
					switch ($iPrecision)
					{
						case 4:
							$sTranslated = 'float';
							break;
						case 8:
							$sTranslated = 'double';
							break;
					}
					break;
				case 'int':
					switch ($iPrecision)
					{
						case 2:
							$sTranslated = 'smallint';
							break;
						case 4:
							$sTranslated = 'int';
							break;
						case 8:
							$sTranslated = 'bigint';
							break;
					}
					break;
				case 'longtext':
				case 'text':
					$sTranslated = 'longtext';
					break;
				case 'time':
					$sTranslated = 'time';
					break;
				case 'timestamp':
			//		$sTranslated = 'timestamp';
					$sTranslated = 'datetime';
					break;
				case 'json':
				case 'jsonb':
						$sTranslated = 'json';
					break;
				case 'varchar':
					if ($iPrecision > 0 && $iPrecision < 256)
					{
						$sTranslated =  sprintf("varchar(%d)", $iPrecision);
					}
					if ($iPrecision > 255)
					{
						$sTranslated =  'text';
					}
					break;
			}

			return $sTranslated;
		}

		function TranslateDefault($sDefault, $sType)
		{

			if ($sType === 'text')
			{
				$ret= ''; //default not supported for (shitty) mysql
			}
			// Need Strict comparisons for true/false in case of datatype bolean
			else if ($sDefault === true || $sDefault === 'true' || $sDefault === 'True')
			{
				$ret= 1;
			}
			else if ($sDefault === false || $sDefault === 'false' || $sDefault === 'False')
			{
				$ret= 0;
			}
			else if ($sDefault == 'current_date' || $sDefault == 'current_timestamp')
			{

				if(preg_match('/int/i', $sType))
				{
					$ret= "0"; // not supported in Mysql, might have to use trigger... "UNIX_TIMESTAMP()";
				}
				else
				{
					$ret= 'CURRENT_TIMESTAMP';
				}

			}
			else
			{
				$ret= "'" . $sDefault . "'";
			}
			return $ret;
		}

		/* Inverse of above, convert sql column types to array info */
		function rTranslateType($sType, $iPrecision = 0, $iScale = 0)
		{
			$sTranslated = '';
			if ($sType == 'int' || $sType == 'tinyint' ||  $sType == 'smallint' || $sType == 'bigint')
			{
				if ($iPrecision > 8)
				{
					$iPrecision = 8;
				}
				elseif($iPrecision > 4)
				{
					$iPrecision = 4;
				}
				else
				{
					$iPrecision = 2;
				}
			}
			switch($sType)
			{
				case 'tinyint':
				case 'smallint':
					$sTranslated = "'type' => 'int', 'precision' => 2";
					break;
				case 'int':
					$sTranslated = "'type' => 'int', 'precision' => 4";
					break;
				case 'bigint':
					$sTranslated = "'type' => 'int', 'precision' => 8";
				case 'char':
					if ($iPrecision > 0 && $iPrecision < 256)
					{
						$sTranslated = "'type' => 'char', 'precision' => $iPrecision";
					}
					if ($iPrecision > 255)
					{
						$sTranslated =  "'type' => 'text'";
					}
					break;
				case 'decimal':
					$sTranslated = "'type' => 'decimal', 'precision' => $iPrecision, 'scale' => $iScale";
					break;
				case 'float':
				case 'double':
					$sTranslated = "'type' => 'float', 'precision' => $iPrecision";
					break;
				case 'datetime':
					$sTranslated = "'type' => 'datetime'"; // longer range..
					break;
				case 'timestamp':
					$sTranslated = "'type' => 'timestamp'";
					break;
				case 'enum':
					/* Here comes a nasty assumption */
					/* $sTranslated =  "'type' => 'varchar', 'precision' => 255"; */
					$sTranslated =  "'type' => 'varchar', 'precision' => $iPrecision";
					break;
				case 'varchar':
					if ($iPrecision > 0 && $iPrecision < 256)
					{
						$sTranslated =  "'type' => 'varchar', 'precision' => $iPrecision";
					}
					if ($iPrecision > 255)
					{
						$sTranslated =  "'type' => 'text'";
					}
					break;
				case 'longtext':
				case 'text':
				case 'blob':
				case 'date':
				case 'json':
					$sTranslated = "'type' => '$sType'";
					break;
				case 'jsonb':
					$sTranslated = "'type' => 'json'";
					break;
			}
			return $sTranslated;
		}

		function GetPKSQL($sFields)
		{
			return "PRIMARY KEY($sFields)";
		}

		function GetUCSQL($sFields)
		{
			return "UNIQUE KEY ($sFields)";
		}

		function GetIXSQL($sFields, $field_type = '')
		{
			$index_type = 'btree';

			if( in_array($field_type, array('jsonb', 'json')))
			{
				/*
				*FIXME
				*/
				return '';
			}

			$this->indexes_sql[str_replace(',','_',$sFields)] = "CREATE INDEX __index_name__ ON __table_name__ ($sFields) USING {$index_type}";
			return '';

		}

		// foreign key supports needs MySQL 3.23.44 and up with InnoDB or MySQL 5.1
		// or other versions the syntax is parsed in table create commands
		// see chapter 1.8.4.5

		function GetFKSQL($reftable, $sFields)
		{
			if(is_array($sFields))
			{
				$ret = "FOREIGN KEY (".implode(',',array_keys($sFields)).")\n" .
					"  REFERENCES $reftable(".implode(',',array_values($sFields)).")";
				return $ret;
			}
			else
			{
				return ""; // incorrect FK declaration found
			}
		}


		function _GetColumns($oProc, $sTableName, &$sColumns, $sDropColumn = '')
		{
			$sColumns = '';
			$this->pk = array();
			$this->fk = array();
			$this->ix = array();
			$this->uc = array();

			/* Field, Type, Null, Key, Default, Extra */
			$stmt = $oProc->m_odb->prepare("DESCRIBE $sTableName");
			$stmt->execute();

			while ($row = $stmt->fetch(PDO::FETCH_NUM))
			{
				$type = $default = $null = $nullcomma = $prec = $scale = $ret = $colinfo = $scales = '';
				if ($sColumns != '')
				{
					$sColumns .= ',';
				}
				$sColumns .= $row[0];

				/* The rest of this is used only for SQL->array */
				$colinfo = explode('(',$row[1]);

				if(isset($colinfo[1]))
				{
					$prec = str_replace(')','',$colinfo[1]);
					$scales = explode(',',$prec);    
				}

				if($colinfo[0] == 'enum')
				{
					/* set prec to length of longest enum-value */
					$prec = 0;
					foreach($scales as $nul => $name)
					{
						if($prec < (strlen($name) - 2))
						{
							/* -2 as name is like "'name'" */
							$prec = (strlen($name) - 2);
						}
					}
				}
				else if ( isset($scales[1]) )
				{
					$prec  = $scales[0];
					$scale = $scales[1];
				}
				$type = $this->rTranslateType($colinfo[0], $prec, $scale);

				if ($row[2] == 'YES')
				{
					$null = "'nullable' => True";
				}
				else
				{
					$null = "'nullable' => False";
				}
				if ($row[4] != '')
				{
					$default = "'default' => '".$row[4]."'";
					$nullcomma = ',';
				}
				else
				{
					$default = '';
					$nullcomma = '';
				}
				if ($row[5])
				{
					$type = "'type' => 'auto'";
				}
				$this->sCol[] = "\t\t\t\t'" . $row[0]."' => array(" . $type . ',' . $null . $nullcomma . $default . '),' . "\n";
				if ($row[3] == 'PRI')
				{
					$this->pk[] = $row[0];
				}
				if ($row[3] == 'UNI')
				{
					$this->uc[] = $row[0];
				}
				/* Hmmm, MUL could also mean unique, or not... */
				if ($row[3] == 'MUL')
				{
					$this->ix[] = $row[0];
				}
			}
			/* ugly as heck, but is here to chop the trailing comma on the last element (for php3) */
			$this->sCol[count($this->sCol) - 1] = substr($this->sCol[count($this->sCol) - 1],0,-2) . "\n";

			return false;
		}

		function DropTable($oProc, &$aTables, $sTableName)
		{
			return !!($oProc->m_odb->exec("DROP TABLE " . $sTableName));
		}

		function DropView($oProc, $sViewName)
		{
			return !!($oProc->m_odb->exec("DROP VIEW " . $sViewName));
		}

		function DropColumn($oProc, &$aTables, $sTableName, $aNewTableDef, $sColumnName, $bCopyData = true)
		{
			return !!($oProc->m_odb->exec("ALTER TABLE {$sTableName} DROP COLUMN {$sColumnName}"));
		}

		function RenameTable($oProc, &$aTables, $sOldTableName, $sNewTableName)
		{
			return !!($oProc->m_odb->exec("ALTER TABLE {$sOldTableName} RENAME {$sNewTableName}"));
		}

		function RenameColumn($oProc, &$aTables, $sTableName, $sOldColumnName, $sNewColumnName, $bCopyData = true)
		{
			/*
			 TODO: This really needs testing - it can affect primary keys, and other table-related objects
			 like sequences and such
			*/
			global $DEBUG;
			if ($DEBUG)
			{
				echo '<br>RenameColumn: calling _GetFieldSQL for ' . $sNewColumnName;
			}

			$sNewColumnSQL = '';

			if (isset($aTables[$sTableName]["fd"][$sNewColumnName]) && $oProc->_GetFieldSQL($aTables[$sTableName]["fd"][$sNewColumnName], $sNewColumnSQL, $sTableName, $sOldColumnName))
			{
				return !!($oProc->m_odb->exec("ALTER TABLE $sTableName CHANGE $sOldColumnName $sNewColumnName " . $sNewColumnSQL));
			}
			return false;
		}

		function AlterColumn($oProc, &$aTables, $sTableName, $sColumnName, &$aColumnDef, $bCopyData = true)
		{
			global $DEBUG;
			if ($DEBUG)
			{
	//			echo '<br>AlterColumn: calling _GetFieldSQL for ' . $sNewColumnName;
			}
			$sNewColumnSQL = '';
			if (isset($aTables[$sTableName]["fd"][$sColumnName]) && $oProc->_GetFieldSQL($aTables[$sTableName]["fd"][$sColumnName], $sNewColumnSQL, $sTableName, $sColumnName))
			{
				return !!($oProc->m_odb->exec("ALTER TABLE $sTableName MODIFY $sColumnName " . $sNewColumnSQL));
				/* return !!($oProc->m_odb->query("ALTER TABLE $sTableName CHANGE $sColumnName $sColumnName " . $sNewColumnSQL)); */
			}

			return false;
		}

		function AddColumn($oProc, &$aTables, $sTableName, $sColumnName, &$aColumnDef)
		{
			$sFieldSQL = '';
			$oProc->_GetFieldSQL($aColumnDef, $sFieldSQL, $sTableName, $sColumnName);
			$query = "ALTER TABLE $sTableName ADD COLUMN $sColumnName $sFieldSQL";

			return !!($oProc->m_odb->exec($query));
		}

		function GetSequenceSQL($sTableName, &$sSequenceSQL)
		{
			$sSequenceSQL = '';
			return false;
		}

		function GetTriggerSQL($sTableName, $sColumnNames, &$sTriggerSQL)
		{
			$sTriggerSQL = '';
			return false;
		}

		function CreateTable($oProc, &$aTables, $sTableName, $aTableDef)
		{
			global $DEBUG;

			$this->indexes_sql = array();
			$sTableSQL = $sSequenceSQL = $sTriggerSQL = '';
			if ($oProc->_GetTableSQL($sTableName, $aTableDef, $sTableSQL, $sSequenceSQL, $sTriggerSQL))
			{
				/* create sequence first since it will be needed for default */
				if ($sSequenceSQL != '')
				{
					$oProc->m_odb->query($sSequenceSQL, __LINE__, __FILE__);
				}

				$query = "CREATE TABLE $sTableName ($sTableSQL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

				if ($DEBUG)
				{
					echo '<br>CREATE TABLE STATEMENT: ';
					var_dump($query);
				}

				$result = !!($oProc->m_odb->exec($query));
				if($result==True)
				{
					if (isset($this->indexes_sql) && $DEBUG)
					{
						echo  '<pre>';
						print_r($this->indexes_sql);
						echo '</pre>';
					}

					if(isset($this->indexes_sql) && is_array($this->indexes_sql) && count($this->indexes_sql)>0)
					{
						foreach($this->indexes_sql as $key => $sIndexSQL)
						{
							$ix_name = str_replace(',','_',$key).'_'.$sTableName.'_idx';
							$IndexSQL = str_replace(array('__index_name__','__table_name__'), array($ix_name,$sTableName), $sIndexSQL);
							$oProc->m_odb->exec($IndexSQL);
						}
					}
				}
				return $result;
			}

			return false;
		}

		function AlterTable( $oProc, &$aTables, $sTableName, $aTableDef )
		{
			global $DEBUG;

			if(!$aTableDef['fk'])
			{
				return true; // nothing to do
			}

			if (is_array($aTableDef['fk']))
			{
				foreach ( $aTableDef['fk'] as $foreign_table => $foreign_key)
				{
					$sFKSQL = '';
					$oProc->_GetFK(array($foreign_table => $foreign_key), $sFKSQL);
					$local_key = implode('_',array_keys($foreign_key));
					/**
					 * FIXME: max length of contraint name: 64
					 */

					 if(strlen($local_key) > 63)
					 {
						$local_key = md5($local_key);
					 }

					$query = "ALTER TABLE $sTableName ADD CONSTRAINT {$sTableName}_{$local_key}_fk $sFKSQL";
				//	if ( $DEBUG)
					{
						echo '<pre>';
						print_r($query);
						echo '</pre>';
					}

					$result = !!$oProc->m_odb->exec($query);
					if(!$result)
					{
						break;
					}
				}

				return $result;
			}

			return false;
		}


	}

