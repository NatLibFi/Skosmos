<?php

interface BaseAuthInterface {

	public function __construct( Controller $controller );

	/**
	 * Validate if all requirements are met to load this
	 * authentication method. This function is useful for
	 * validating configuration options inside Config.ttl.
	 *
	 * @return bool
	 */
	public function validate() :bool;

	/**
	 * Checks if the current user has a valid session on the
	 * authorization endpoint.
	 *
	 * @return bool
	 */
	public function isSignedIn() : bool;

	/**
	 * Signs in the current user
	 *
	 * @return mixed
	 */
	public function signIn();

	/**
	 * Signs out the user
	 *
	 * @return mixed
	 */
	public function signOut();

	/**
	 * Retrieve the user's attributes
	 *
	 * @return array
	 */
	public function getUserAttributes() : array;


}