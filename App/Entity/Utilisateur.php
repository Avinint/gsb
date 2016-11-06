<?php

namespace App\Entity;

use \App;
use Core\Entity\Entity;
use Core\Entity\UserInterface;

class Utilisateur extends Entity implements UserInterface{
	
	protected $id;
    protected $pseudo;
	protected $nom;
	protected $prenom;
	protected $email;
	protected $mdp;
    protected $image;
	protected $role_id;
    protected $newsletter;

    /**
     * @return string
     */
	public function getId()
	{
		return $this->id;
	}

    /**
     * @param mixed $pseudo
     */
    public function setPseudo($pseudo)
    {
        $this->pseudo = $pseudo;
    }

    /**
     * @return mixed
     */
    public function getPseudo()
    {
        return $this->pseudo;
    }


    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $mdp
     */
    public function setMdp($mdp)
    {
        $this->mdp = $mdp;
    }

    /**
     * @return mixed
     */
    public function getMdp()
    {
        return $this->mdp;
    }

    /**
     * @param mixed $nom
     */
    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    /**
     * @return mixed
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param mixed $prenom
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;
    }

    /**
     * @return mixed
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * @param mixed $roleId
     */
    public function setRole_id($roleId)
    {
        $this->role_id = $roleId;
    }

    /**
     * @return mixed
     */
    public function getRole_id()
    {
        return $this->role_id;
    }

    /**
     * @param mixed $newsletter
     */
    public function setNewsletter($newsletter)
    {
        $this->newsletter = $newsletter;
    }

    /**
     * @return boolean
     */
    public function getNewsletter()
    {
        return $this->newsletter;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    public function getFilePath()
    {
        return  ROOT.D_S.'public'.D_S.'img'.D_S.'avatars';
    }
}