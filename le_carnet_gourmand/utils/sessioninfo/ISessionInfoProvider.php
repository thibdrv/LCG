<?php

interface ISessionInfoProvider {
	
	function getComptePk(): ?int;
	
	function getRolePk(): ?int;
	
}