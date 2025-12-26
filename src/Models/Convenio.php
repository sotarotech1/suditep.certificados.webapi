<?php

	require_once __DIR__ . "/../Db/Database.php";

	class Convenio extends Connection
	{

		private $table = "convenios";

		public function __construct()
		{
			parent::__construct();
		}

		public function getConvenios()
		{
			try {
				$conn = $this->connect();

				$sql = "SELECT * FROM {$this->table}";
				$stmt = $conn->prepare($sql);
				$stmt->execute();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $result;
			} catch (Exception $e) {
				error_log("Convenios error: " . $e->getMessage());
        		return 0;
			}
		}

		public function getConvenioById(int $id)
		{
			try {
				$conn = $this->connect();

				$sql = "SELECT * FROM {$this->table} WHERE idConvenio=?";
				$stmt = $conn->prepare($sql);
				$stmt->execute([ $id ]);
				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				return $result;
			} catch (Exception $e) {
				error_log("Convenios error: " . $e->getMessage());
        		return 0;
			}
		}

		public function createConvenio(array $data)
		{
			try {
				$conn = $this->connect();

				$sql = "INSERT INTO {$this->table}(nameConvenio, subTitleConvenio, nroConvenio, rutaLogoConvenio, rutaFirmaConvenio) VALUES(?, ?, ?, ?, ?)";
				$stmt = $conn->prepare($sql);
				$stmt->execute([
					$data['nombre'], $data['subtitle'], $data['nroConvenio'], $data['rutaLogo'], $data['rutaFirma']
				]);
				return [ 'success' => true ];
			} catch (Exception $e) {
				error_log('Insert Convenio Error' . $e->getMessage());
                return null;
			}
		}

	}