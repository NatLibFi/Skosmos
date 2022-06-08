<?php

class SimpleSamlPHP implements BaseAuthInterface {

	/**
	 * @var Model
	 */
	private $model;

	/**
	 * Placeholder of the SimpleSamlPHP authentication entity.
	 *
	 * @var string|null
	 */
	private $authEntity;

	/**
	 * The configured application URL
	 *
	 * @var string
	 */
	private $baseHref;

	/**
	 * @param Model $model
	 */
	public function __construct( Controller $controller ) {
		$this->model = $controller->model;
		$this->baseHref = $controller->getBaseHref();
	}

	/**
	 * @inheritDoc
	 */
	public function validate(): bool {
		$authDirectory = $this->model->getConfig()->getLiteral( 'skosmos:authProviderIncludeDirectory');
		if ( !$authDirectory ) {
			return false;
		}

		$authEntity = $this->model->getConfig()->getLiteral('skosmos:authProviderAuthEntity');
		if ( !$authEntity ) {
			return false;
		} else {
			$this->authEntity = $authEntity;
		}

		$sspAutoloader = $authDirectory . DIRECTORY_SEPARATOR . 'lib/_autoload.php';
		if ( !file_exists( $sspAutoloader ) ) {
			return false;
		} else {
			require $sspAutoloader;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isSignedIn(): bool {
		return $this->getSessionFromRequest()->isValid( $this->authEntity );
	}

	/**
	 * @inheritDoc
	 */
	public function signIn() {
		$this->getAuthenticationSource()->requireAuth( [
			'ReturnTo' => $this->baseHref
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function signOut() {
		$this->getAuthenticationSource()->logout( [
			'ReturnTo' => $this->baseHref
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getUserAttributes(): array {
		if ( $this->isSignedIn() ) {
			return [];
		}
		return [
			'auth_source' => $this->authEntity,
			'attributes' => $this->getAuthenticationSource()->getAttributes()
		];
	}

	/**
	 * @return \SimpleSAML\Auth\Simple
	 */
	private function getAuthenticationSource( ) {
		return new SimpleSAML\Auth\Simple( $this->authEntity );
	}

	/**
	 * @return SimpleSAML\Session::getSessionFromRequest()
	 */
	private function getSessionFromRequest() {
		return SimpleSAML\Session::getSessionFromRequest();
	}

}