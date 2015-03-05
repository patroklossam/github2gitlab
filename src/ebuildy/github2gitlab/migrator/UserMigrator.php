<?php

namespace ebuildy\github2gitlab\migrator;

class UserMigrator extends BaseMigrator
{
    public function getUsersMap()
    {
        return $this->usersMap;
    }

    public function run($dry = true)
    {
        $githubUsers  = $this->githubClient->organization()->members()->all($this->organization);
        $gitlabUsers   = $this->gitlabClient->users->all(null, 1, 100);

        foreach($githubUsers as $githubMember)
        {
            $searchExistingUser = null;

            foreach($gitlabUsers as $gitlabUser)
            {
                if ($gitlabUser['username'] === $githubMember['login'])
                {
                    $searchExistingUser = $gitlabUser;

                    break;
                }
            }

            if (empty($searchExistingUser))
            {
                $this->output('[User] Insert ' . $githubMember['login']);

                if (!$dry)
                {
                    $gitlabUser = $this->gitlabClient->users->create($githubMember['login'] . '@email.com',
                        DEFAULT_PASSWORD, [
                            'extern_uid'  => $githubMember['id'],
                            'name'        => $githubMember['login'],
                            'username'    => $githubMember['login'],
                            'admin'       => $githubMember['site_admin'],
                            'confirm'     => false
                        ]);
                }
            }
            else
            {
                $gitlabUser = $searchExistingUser;
            }

            $this->output('Login ' . $gitlabUser['username'] . ' ...');

            sleep(10);

            try
            {
                $session = $this->gitlabClient->users->login($gitlabUser['username'], DEFAULT_PASSWORD);

                $gitlabUser['token'] = $session['private_token'];
            }
            catch (\Exception $e)
            {
                throw $e;
                $this->output('<!> Cannot retrieve private_token for "' . $gitlabUser['username'] . '"' . $e->getMessage());die();
            }

            $this->usersMap[$githubMember['id']] = $gitlabUser;
        }
    }
}