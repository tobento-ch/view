<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Acl;

/**
 * Acl
 */
class Acl implements AclInterface
{
    use HasPermissions;
    
    /**
     * @var null|Authorizable
     */
    protected ?Authorizable $currentUser = null;

    /**
     * @var string
     */
    protected string $defaultRuleArea = 'default';
    
    /**
     * @var array The rules.
     */
    protected array $rules = [];
    
    /**
     * @var null|RolesInterface
     */
    protected null|RolesInterface $roles = null;
    
    /**
     * Set the current user.
     *
     * @param Authorizable $user
     * @return static $this
     */
    public function setCurrentUser(Authorizable $user): static
    {
        $this->currentUser = $user;
        return $this;
    }
 
    /**
     * Get the current user.
     *
     * @return null|Authorizable
     */
    public function getCurrentUser(): ?Authorizable
    {
        return $this->currentUser;
    }
    
    /**
     * Set the default rule area.
     *
     * @param string $area
     * @return static $this
     */
    public function setDefaultRuleArea(string $area): static
    {
        $this->defaultRuleArea = $area;
        return $this;
    }

    /**
     * Create and adds a new Rule.
     *
     * @param string $key A rule key
     * @return Rule
     */
    public function rule(string $key): Rule
    {
        $rule = new Rule($key);
        $rule->area($this->defaultRuleArea);
        $this->addRule($rule);
        return $rule;
    }
    
    /**
     * Adds a rule.
     *
     * @param RuleInterface $rule
     * @return static $this
     */
    public function addRule(RuleInterface $rule): static
    {
        $this->rules[$rule->getKey()] = $rule;
        return $this;
    }

    /**
     * Check if the given permission are set.
     *
     * @param string $key A permission key 'user.create' or multiple keys 'user.create|user.update'
     * @param array $parameters Any parameters for custom handler
     * @param null|Authorizable $user If null current user is taken.
     * @return bool True on success, false on failure.
     */
    public function can(string $key, array $parameters = [], ?Authorizable $user = null): bool
    {        
        if (! str_contains($key, '|')) {
            return (bool) $this->getRule($key)?->matches($this, $key, $parameters, $user);
        }
        
        foreach(explode('|', $key) as $key) {
            if ($this->can($key, $parameters[$key] ?? [], $user) === false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if permission is not given.
     *
     * @param string $key A permission key 'user.create' or multiple keys 'user.create|user.update'
     * @param array $parameters Any parameters for custom handler
     * @param null|Authorizable $user If null current user is taken.
     * @return bool True no permission, false has permission.
     */
    public function cant(string $key, array $parameters = [], ?Authorizable $user = null): bool
    {
        return ! $this->can($key, $parameters, $user);
    }
    
    /**
     * Sets the roles.
     *
     * @param RolesInterface|array<array-key, RoleInterface> $roles
     * @return static $this
     */
    public function setRoles(RolesInterface|array $roles): static
    {
        if (is_array($roles)) {
            $this->roles = new Roles(...$roles);
            return $this;
        }
        
        $this->roles = $roles;
        return $this;
    }
    
    /**
     * Gets the roles.
     *
     * @param null|string $area An area key such as 'frontend' or null to get all roles.
     * @return array<string, RoleInterface>
     */
    public function getRoles(?string $area = null): array
    {
        if (is_null($area)) {
            return $this->roles()->all();
        }
        
        return $this->roles()->area($area)->all();
    }
    
    /**
     * Gets the roles.
     *
     * @return RolesInterface
     */
    public function roles(): RolesInterface
    {
        return $this->roles ?: new Roles();
    }

    /**
     * Gets the role by key.
     *
     * @param string $key The role key such as 'frontend'.
     * @return null|RoleInterface
     */
    public function getRole(string $key): ?RoleInterface
    {
        return $this->roles()->get($key);
    }

    /**
     * Whether a role by key exists.
     *
     * @param string $key The role key such as 'frontend'.
     * @return bool If role exists.
     */
    public function hasRole(string $key): bool
    {
        return $this->roles()->has($key);
    }
        
    /**
     * Gets the rules.
     *
     * @return array The rules
     */
    public function getRules(): array
    {
        return $this->rules;
    }
    
    /**
     * Gets a rule or null
     *
     * @param string $ruleKey The rule key. 'user.create'
     * @return null|RuleInterface Null if rule does not exist.
     */
    public function getRule(string $ruleKey): ?RuleInterface
    {
        return $this->rules[$ruleKey] ?? null;
    }
}