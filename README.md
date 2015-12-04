# Feature Branch Plugin

Composer plugin to allow easy-peasy management of feature branches across multiple repositories

## Usage

Update your composer.json file to include the repositories you'd like to watch. 

Subsequent `composer update` calls will use matching feature branches (or fall back to the specified dependency in composer.json)

### composer.json

```
	"extra": {
      "feature-branch-repositories": [
        "crusepartnership/super-repo"
      ]
    }     
```





