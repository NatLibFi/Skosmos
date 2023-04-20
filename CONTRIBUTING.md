# Contributing to Skosmos

Thank you for taking the time to contribute! :+1:

Here you'll find some general guidelines for contributing to the development of Skosmos. If you'd like to discuss Skosmos with other users and developers visit the [Skosmos user forum](https://groups.google.com/forum/#!forum/skosmos-users). The list is used for general discussion about Skosmos, asking for help, and announcements for new versions. All messages are public and anyone is welcome to join!

## Reporting a bug

First ensure the bug has not been already reported by searching the [issues](https://github.com/NatLibFi/Skosmos/issues/) here on GitHub. If it has been already reported you can help by providing additional information as a comment to the existing issue.

If you've found a bug that hasn't been reported yet you can help us by [creating a new issue](https://github.com/NatLibFi/Skosmos/issues/new). Fill out the information requested in the issue template. Include a clear title and description of the bug you've found with as much information as possible. Also include a link to a page where the error can be seen. If adding a url is not possible describe the actions needed to reproduce the error.

## Providing translations

Skosmos core languages (English, Finnish and Swedish) are maintained by the National Library of Finland. In addition to these three languages volunteers have provided translations for other languages on the transifex platform. We are happy to receive both translations for new languages as well as **updates to the existing translations**. Skosmos has been translated to more than 10 languages by members of the community.

If you'd like to provide translation help the instructions at the [translation wiki page](https://github.com/NatLibFi/Skosmos/wiki/Translation) will tell you how to do it.

## Contributing code

### Development cycles and use of milestones
Skosmos development is done in sprints, usually lasting two weeks, followed by a release. The planned work (issues) for the next/current sprint is chosen from among the open issues. Issues or pull requests may have a milestone indicating the version number of the planned release eg. **2.16**. The issues marked in the milestone **Next tasks** are issues we haven't yet scheduled for a specific release but we'd like resolve in the near future. Issues marked **Blue sky** are issues we've decided to postpone for now. The milestones and open issues are evaluated after each release.

### Making a commit

If you see an issue that you'd like to fix feel free to do so. If possible let us know you're working on an issue by leaving a comment on it so we'll be able to avoid doing the same work twice. This is especially useful if the issue has been marked for a release (in a milestone with a version number eg. "1.8") since it's more likely someone might be already working on it.

### Code style

Skosmos PHP code should follow [PSR-12](https://www.php-fig.org/psr/psr-12/) style. To help achieve this, [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer), a tool to automatically fix and verify PHP code style issues, is included as a development dependency that is installed via Composer.  There is also a code style check in the GitHub Actions CI pipeline that verifies the code style compliance.

You can set up a pre-commit hook to automate style checking with PHP-CS-Fixer for every git commit by adding the following script to the file `.git/hooks/pre-commit`, which should have execute permission set:

```bash
#!/bin/bash

set -e
vendor/bin/php-cs-fixer fix --diff --dry-run src
```

If the hook complains and intercepts the commit, you can run PHP-CS-Fixer manually to reformat the PHP code using this command:

    vendor/bin/php-cs-fixer fix src

We expect contributors to code according to good coding practices also for front-end code. The Mozilla Developer Network has a strong position in the JavaScript/ECMAScript developer community, so we follow their example and use the [Prettier tool](https://prettier.io/docs/en/index.html) to validate the code.

[Guidelines for styling JavaScript code examples](https://developer.mozilla.org/en-US/docs/MDN/Writing_guidelines/Writing_style_guide/Code_style_guide/JavaScript#general_guidelines_for_javascript_code_examples)

To test how the Prettier tool works and what the rules are, you can visit the online version [here](https://prettier.io/playground/).

Prettier validates and fixes the code with a pre-commit hook run on git commit. If there are style errors in the code, Prettier automatically corrects them in the files containing the code. If there are syntax errors, Prettier does not fix them, but will show you the errors that must be corrected before the commit can be performed successfully.

If Prettier works unexpectedly, please let us know in the Skosmos [Users group](https://groups.google.com/g/skosmos-users).

#### How to deploy Prettier in your Skosmos installation

Add the following line at the end of the pre-commit file you already edited earlier related to PHP-CS-Fixer:
```
npx prettier --config prettier.config.js --write *.vue *.js
```

### Unit tests

If you've added new functionality or you've found out that the existing tests are lacking, we'd be happy if you could provide additional PHPUnit tests to cover it. Also see that your code passes all the existing tests by running PHPUnit. For more information, see the wiki page on [unit and integration tests](https://github.com/NatLibFi/Skosmos/wiki/Unit-and-integration-tests).

### Making a commit and a pull request

Please add a concise commit message for your contributions. In addition to describing the changes made include references to related issue numbers eg. "Description of the changes in the commit, related to #22". Then the GitHub issue tracker automatically links the related commits to the issue page. This makes debugging persistent bugs easier.

When you're happy with your finished contribution please send us a [pull request](https://help.github.com/articles/about-pull-requests/).
