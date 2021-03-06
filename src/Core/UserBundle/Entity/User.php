<?php

namespace Core\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use EBM\KMBundle\Entity\Document;
use EBM\KMBundle\Entity\DocumentHistory;
use EBM\SocialNetworkBundle\Entity\ProjectSubscription;
use EBM\SocialNetworkBundle\Entity\Publication;
use EBM\SocialNetworkBundle\Entity\SocialComment;
use EBM\KMBundle\Entity\EvaluationDocument;
use EBM\UserInterfaceBundle\Entity\Project;
use EBM\KMBundle\Entity\CompetenceUser;
use EBM\KMBundle\Entity\Tag;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * @ORM\Table(name="core_user")
 * @ORM\Entity(repositoryClass="Core\UserBundle\Repository\UserRepository")
 * @UniqueEntity("email")
 */
class User extends BaseUser
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /* 
     * Attributs de l'utilisateur déjà compris avec FOSUserBundle
     * username
     * email
     * enabled (si inscription valide)
     * password
     * lastLogin
     * locked
     * expired
     */
    
    /**
    * @ORM\Column(name="fullname", type="string", length=55)
    * @Assert\Length(min=2, minMessage="Le nom complet doit au moins comprendre 2 caractères",max=30, maxMessage="Le nom complet ne peut excéder 50 caractères.")
    */
    private $fullname="";

    /**
     * @ORM\Column(name="surname", type="string", length=55)
     * @Assert\Length(min=2, minMessage="Le nom de famille doit au moins comprendre 2 caractères",max=30, maxMessage="Le nom de famille ne peut excéder 50 caractères.")
     */
    private $surname;

    /**
     * @ORM\Column(name="name", type="string", length=55)
     * @Assert\Length(min=2, minMessage="Le prénom doit au moins comprendre 2 caractères",max=30, maxMessage="Le prénom ne peut excéder 50 caractères.")
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="promotion", type="integer", nullable=true)
     */
    private $promotion;

    /**
     * @ORM\Column(name="global_role", type="string", length=5)
     */
    private $global_role;

    /**
     * @@ORM\Column(name="image", type="string", length=255)
     */
    private $image;

    /**
     * @ORM\ManyToMany(targetEntity="EBM\UserInterfaceBundle\Entity\Project", inversedBy="members")
     * @ORM\JoinTable(name="core_user_project")
     */
    private $projects;

    /**
     * @ORM\ManyToMany(targetEntity="EBM\KMBundle\Entity\Tag", cascade= {"persist"})
     * @ORM\JoinTable(name="sn_tag_subscription")
     */
    private $tags;

    /**
     * @ORM\ManyToMany(targetEntity="EBM\UserInterfaceBundle\Entity\Project", cascade= {"persist"})
     * @ORM\JoinTable(name="sn_project_subscription")
     */
    private $projectSubscriptions;


    /**
     * @var string
     *
     * @ORM\Column(name="`desc`", type="text", nullable=true)
     */
    private $desc;

    /**
     * @var string
     *
     * @ORM\Column(name="timezone", type="string", length=100)
     */
    private $timezone="Europe/Paris";

    /**
     * @ORM\ManyToMany(targetEntity="EBM\KMBundle\Entity\Post", mappedBy= "identifiedUsers", cascade= {"persist"})
     */
    private $postIdentified;

    /**
     * @ORM\OneToMany(targetEntity="EBM\KMBundle\Entity\Post", mappedBy= "author", cascade= {"persist"})
     */
    private $authorOf;

    /**
     * @ORM\OneToMany(targetEntity="EBM\KMBundle\Entity\Vote", mappedBy= "user", cascade= {"persist"})
     */
    private $postVoted;

    /**
     * @Orm\OneToMany(targetEntity="EBM\KMBundle\Entity\CompetenceUser", mappedBy="user", cascade={"persist"})
     */
    private $skills;


    /**
     * @Orm\OneToMany(targetEntity="EBM\SocialNetworkBundle\Entity\Publication", mappedBy="userPublication", cascade={"persist"})
     */
    private $publications;

    /**
     * @Orm\OneToMany(targetEntity="EBM\SocialNetworkBundle\Entity\SocialComment", mappedBy="userComment", cascade={"persist"})
     */
    private $socialComments;

    /**
     * @ORM\OneToMany(targetEntity="EBM\KMBundle\Entity\EvaluationDocument", mappedBy="author", cascade= {"persist"})
     */
    private $documentEvaluations;

    /**
     * @ORM\OneToMany(targetEntity="EBM\KMBundle\Entity\DocumentHistory", mappedBy= "author", cascade= {"persist"})
     */
    private $createDocument;

    /**
     * @Orm\ManyToMany(targetEntity="EBM\KMBundle\Entity\CompetenceUser", mappedBy="recommendations", cascade={"persist"})
     * @Orm\JoinTable("km_recommend_skill")
     */
    private $recommendSkill;

    /**
     * @Orm\ManyToMany(targetEntity="EBM\KMBundle\Entity\Tag", mappedBy="referents", cascade={"persist"})
     * @Orm\JoinTable("km_tag_managers")
     */
    private $managedTags;

    /**
     * @ORM\ManyToMany(targetEntity="EBM\GDPBundle\Entity\Task", mappedBy="membersAssigned")
     */
    private $gdpTasks;

    /**
     * @ORM\OneToMany(targetEntity="EBM\GDPBundle\Entity\Comment", mappedBy= "utilisateur", cascade={"persist"})
     */
    private $comments;

    /* qui des attributs locked & co hérités du FosUserBundle ?
     
    Enabled = true
    User is verified, that means user is email owner for sure. This can be verified by resseting password or clicking on confirmation email after registration.
    This flag should not be touched by admin or other user.
    If enabled = false DisabledException is thrown
    
    Locked = true
    User is forbidden to manipulate his account, because it is locked down. That means no password reset, login etc.
    This flag allows admin to ban user or don't let him register with his email again.
    LockedException is thrown.
    
    Expired = true
    User is archived by admin or after some time from last login (CRON service?).~~ When he logs again, revalidation is required.~~
    This flag allows admin to force user to revalidate himself, change his password or use it as an inactive users archive, which can't login.
    AccountExpiredException is thrown.
    
    CredentialsExpired = true
    This is checked after login and if true, user should be forced to change his password and revalidate himself.
    CredentialsExpiredException is thrown.

    Flags are checked in this order:
    1. Locked
    2. Enabled
    3. Expired
    4. CredentialsExpired
     */

    // Notez le singulier, on ajoute un seul projet à la fois
    public function addProject(Project $project)
    {
        // Ici, on utilise l'ArrayCollection vraiment comme un tableau
        $this->projects[] = $project;
        $project->addMember($this);
    }

    public function removeProject(Project $project)
    {
        // Ici on utilise une méthode de l'ArrayCollection, pour supprimer le projet en argument
        $this->projects->removeElement($project);
        $project->removeMember($this);
    }

    // Notez le pluriel, on récupère une liste de projets ici !
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }

    /**
     * @return int
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * @param int $promotion
     */
    public function setPromotion($promotion)
    {
        $this->promotion = $promotion;
    }

    public function getHighestRole()
    {
        $rolesSortedByImportance = ['ROLE_ADMIN', 'ROLE_USER'];
        foreach ($rolesSortedByImportance as $role)
        {
            if (in_array($role, $this->roles))
                return $role;
        }
        return "ROLE_USER";
    }
    

    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->projects = new ArrayCollection();
        $this->postIdentified = new ArrayCollection();
        $this->authorOf = new ArrayCollection();
        $this->postVoted = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->recommendSkill = new ArrayCollection();
        $this->createDocument = new ArrayCollection();
        $this->documentEvaluations = new ArrayCollection();
        $this->managedTags = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }
    
    public function hasRole($role) {
        if(in_array($role, $this->getRoles())) return true;
        return false;
    }

    /* Méthode mise en commentaire car interfère avec le getter de la variable $name */
    /*public function getName()
    {
        return $this->getFullname() != null && strlen($this->getFullname()) > 0 ? $this->getFullname() : $this->getUsername();
    }*/

    /**
     * @param string $username
     * @return string
     */
    public function setUsername($username)
    {
        $this->username = utf8_encode($username);

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return utf8_decode($this->username);
    }
    
    /**
    * @param string $fullname
    */
    public function setFullname($fullname)
    {
        $this->fullname = utf8_encode($fullname);
    }

    /**
     * @return string
     */
    public function getFullname()
    {
        return utf8_decode($this->fullname);
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     *
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    function get_timezone_offset($remote_tz, $origin_tz = null) {
        if($origin_tz === null) {
            if(!is_string($origin_tz = date_default_timezone_get())) {
                return false; // A UTC timestamp was returned -- bail out!
            }
        }
        $origin_dtz = new \DateTimeZone($origin_tz);
        $remote_dtz = new \DateTimeZone($remote_tz);
        $origin_dt = new \DateTime("now", $origin_dtz);
        $remote_dt = new \DateTime("now", $remote_dtz);
        $offset = $remote_dtz->getOffset($remote_dt)-$origin_dtz->getOffset($origin_dt);
        // Exemple : pour paris, on obtiendra + 3600
        return floatval($offset);
    }

    public function getUserGMTSeconds()
    {
        return $this->getTimezone() != null && !empty($this->getTimezone()) ? $offset = $this->get_timezone_offset($this->getTimezone()) : 0;
    }

    public function getUserGMTHours()
    {
        return $this->getUserGMTSeconds()/3600;
    }

    /**
     * @return mixed
     */
    public function getPostIdentified()
    {
        return $this->postIdentified;
    }

    /**
     * @param mixed $postIdentified
     */
    public function setPostIdentified($postIdentified)
    {
        $this->postIdentified = $postIdentified;
    }

    /**
     * @return mixed
     */
    public function getAuthorOf()
    {
        return $this->authorOf;
    }

    /**
     * @param mixed $authorOf
     */
    public function setAuthorOf($authorOf)
    {
        $this->authorOf = $authorOf;
    }

    public function addAuthorOf($authorOf)
    {
        $this->authorOf->add($authorOf);
    }

    public function removeAuthorOf($authorOf)
    {
        $this->authorOf->removeElement($authorOf);
    }

    public function addPostVoted($postVoted)
    {
        $this->postVoted->add($postVoted);
    }

    public function removePostVoted($postVoted)
    {
        $this->postVoted->removeElement($postVoted);
    }
    /**
     * @return mixed
     */
    public function getPostVoted()
    {
        return $this->postVoted;
    }

    /**
     * @param mixed $postVoted
     */
    public function setPostVoted($postVoted)
    {
        $this->postVoted = $postVoted;
    }

    /**
     * @return mixed
     */
    public function getSkills()
    {
        return $this->skills;
    }

    /**
     * @param mixed $skills
     */
    public function setSkills($skills)
    {
        $this->skills = $skills;
    }

    /**
     * @param CompetenceUser $skill
     * @return $this
     */
    public function addSkill(CompetenceUser $skill){
        $skill->setUser($this);
        $this->skills->add($skill);
        return $this;
    }

    /**
     * @param CompetenceUser $skill
     * @return $this
     */
    public function removeSkill(CompetenceUser $skill)
    {
        $this->skills->removeElement($skill);
        return $this;
    }

    public function getDocumentEvaluations()
    {
        return $this->documentEvaluations;
    }

    /**
     * @param mixed $documentEvaluations
     */
    public function setDocumentEvaluations($documentEvaluations)
    {
        $this->documentEvaluations = $documentEvaluations;
    }

    /**
     * @param EvaluationDocument $documentEvaluation
     */
    public function addDocumentEvaluation(EvaluationDocument $documentEvaluation){
        $this->documentEvaluations->add($documentEvaluation);
    }

    /**
     * @param EvaluationDocument $documentEvaluation
     */
    public function removeDocumentEvaluation(EvaluationDocument $documentEvaluation){
        $this->documentEvaluations->removeElement($documentEvaluation);
    }

    /**
     * @return mixed
     */
    public function getRecommendSkill()
    {
        return $this->recommendSkill;
    }

    /**
     * @param mixed $recommendSkill
     */
    public function setRecommendSkill($recommendSkill)
    {
        $this->recommendSkill = $recommendSkill;
    }

    /**
     * @param CompetenceUser $skill
     * @return $this
     *
     */
    public function addRecommendSkill(CompetenceUser $skill){
        $skill->setUser($this);
        $this->recommendSkill->add($skill);
        return $this;
    }

    /**
     * @param CompetenceUser $skill
     * @return $this
     */
    public function removeRecommendSkill(CompetenceUser $skill){
        $this->recommendSkill->removeElement($skill);
        return $this;
    }

    public function getCreateDocument()
    {
        return $this->createDocument;
    }

    /**
     * @param mixed $createDocument
     */
    public function setCreateDocument($createDocument)
    {
        $this->createDocument = $createDocument;
    }

    /**
     * @return ArrayCollection<Tag>
     */
    public function getManagedTags()
    {
        return $this->managedTags;
    }

    /**
     * @param mixed $managedTags
     */
    public function setManagedTags($managedTags)
    {
        $this->managedTags = $managedTags;
    }

    /**
     * @param Tag $tag
     * @return $this
     */
    public function addManagedTag(Tag $tag){
        $this->managedTags->add($tag);
        return $this;
    }

    /**
     * @param Tag $tag
     * @return $this
     */
    public function removeManagedTag(Tag $tag){
        $this->managedTags->removeElement($tag);
        return $this;
    }


    /**
     * Add tag
     *
     * @param \EBM\KMBundle\Entity\Tag $tag
     *
     * @return User
     */
    public function addTag(\EBM\KMBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \EBM\KMBundle\Entity\Tag $tag
     */
    public function removeTag(\EBM\KMBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Add postIdentified
     *
     * @param \EBM\KMBundle\Entity\Post $postIdentified
     *
     * @return User
     */
    public function addPostIdentified(\EBM\KMBundle\Entity\Post $postIdentified)
    {
        $this->postIdentified[] = $postIdentified;

        return $this;
    }

    /**
     * Remove postIdentified
     *
     * @param \EBM\KMBundle\Entity\Post $postIdentified
     */
    public function removePostIdentified(\EBM\KMBundle\Entity\Post $postIdentified)
    {
        $this->postIdentified->removeElement($postIdentified);
    }

    /**
     * Add createDocument
     *
     * @param DocumentHistory $createDocument
     *
     * @return User
     */
    public function addCreateDocument(DocumentHistory $createDocument)
    {
        $this->createDocument[] = $createDocument;

        return $this;
    }

    /**
     * Remove createDocument
     *
     * @param DocumentHistory $createDocument
     */
    public function removeCreateDocument(DocumentHistory $createDocument)
    {
        $this->createDocument->removeElement($createDocument);
    }

    /**
     * Add publication
     *
     * @param Publication $publication
     *
     * @return User
     */
    public function addPublication(Publication $publication)
    {
        $this->publications[] = $publication;

        return $this;
    }

    /**
     * Remove publication
     *
     * @param Publication $publication
     */
    public function removePublication(Publication $publication)
    {
        $this->publications->removeElement($publication);
    }

    /**
     * Get publications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPublications()
    {
        return $this->publications;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Add gdpTask
     *
     * @param \EBM\GDPBundle\Entity\Task $gdpTask
     *
     * @return User
     */
    public function addGdpTask(\EBM\GDPBundle\Entity\Task $gdpTask)
    {
        $this->gdpTasks[] = $gdpTask;

        return $this;
    }

    /**
     * Remove gdpTask
     *
     * @param \EBM\GDPBundle\Entity\Task $gdpTask
     */
    public function removeGdpTask(\EBM\GDPBundle\Entity\Task $gdpTask)
    {
        $this->gdpTasks->removeElement($gdpTask);
    }

    /**
     * Get gdpTasks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGdpTasks()
    {
        return $this->gdpTasks;
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @param mixed $surname
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getGlobalRole()
    {
        return $this->global_role;
    }

    /**
     * @param mixed $global_role
     */
    public function setGlobalRole($global_role)
    {
        $this->global_role = $global_role;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * Add projectSubscription
     *
     * @param \EBM\UserInterfaceBundle\Entity\Project $projectSubscription
     *
     * @return User
     */
    public function addProjectSubscription(Project $projectSubscription)
    {
        $this->projectSubscriptions[] = $projectSubscription;

        return $this;
    }

    /**
     * Remove projectSubscription
     *
     * @param \EBM\UserInterfaceBundle\Entity\Project $projectSubscription
     */
    public function removeProjectSubscription(Project $projectSubscription)
    {
        $this->projectSubscriptions->removeElement($projectSubscription);
    }

    /**
     * Get projectSubscriptions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectSubscriptions()
    {
        return $this->projectSubscriptions;
    }

    /**
     * Add socialComment
     *
     * @param \EBM\SocialNetworkBundle\Entity\SocialComment $comment
     *
     * @return User
     */
    public function addSocialComment(SocialComment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove socialComment
     *
     * @param \EBM\SocialNetworkBundle\Entity\SocialComment $comment
     */
    public function removeSocialComment(SocialComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get socialComments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSocialComments()
    {
        return $this->comments;
    }
}
