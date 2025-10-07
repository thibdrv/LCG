<?php

	// Ça veut dire que toute classe qui en hérite pourra être transformée automatiquement en JSON avec json_encode()
	// Elle permet de sérialiser automatiquement n’importe quel objet (même avec des propriétés privées).
	// Pas besoin d’écrire jsonSerialize() dans chaque entité manuellement.

	require_once(ROOT . "/entities/IEntity.php");

	abstract class AbstractEntity implements IEntity
    {

	// Permet de prendre les champs privés d'une instance de classe et de les rendre disponibles pour la serialisation
	
	function jsonSerialize(): mixed
	{
		$reflection = new ReflectionClass($this);
		$properties = [];

		foreach ($reflection->getProperties() as $property) {
			$property->setAccessible(true);

			if ($property->isInitialized($this)) {
				$properties[$property->getName()] = $property->getValue($this);
			} else {
				// Propriété non initialisée → on met null
				$properties[$property->getName()] = null;
			}

			$property->setAccessible(false);
		}

		return $properties;
	}

}
?>