<?php

interface UserInterface {

	/**
	 * Returns the email-address of the user
	 *
	 * @return mixed
	 */
	public function getEmail();

	/**
	 * Returns the user object as a array
	 *
	 * @return array
	 */
	public function toArray() : array;

}