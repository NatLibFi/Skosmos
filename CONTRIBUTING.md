## Contributing to Skosmos

Thank you for taking the time to contribute! :+1:

Here you'll find some general guidelines for contributing to the development of Skosmos. If you'd like to discuss Skosmos with other users and developers visit the [Skosmos user forum](https://groups.google.com/forum/#!forum/skosmos-users). The list is used for general discussion about Skosmos, asking for help, and announcements for new versions. All messages are public and anyone is welcome to join!

### Reporting a bug

First ensure the bug has not been already reported by searching the [issues](https://github.com/NatLibFi/Skosmos/issues/) here on GitHub. If it has been already reported you can help by providing additional information as a comment to the existing issue.

If you've found a bug that hasn't been reported yet you can help us by [creating a new issue](https://github.com/NatLibFi/Skosmos/issues/new). Fill out the information requested in the issue template. Include a clear title and description of the bug you've found with as much information as possible. Also include a link to a page where the error can be seen. If adding a url is not possible describe the actions needed to reproduce the error.

### Providing translations

Skosmos core languages (English, Finnish and Swedish) are maintained by us. In addition to these three languages volunteers have provided translations for other languages on the transifex platform. We are happy to receive both translations for new languages as well as **updates to the existing translations**. Translations included in the 1.7 release include English, Chinese, Spanish, French, German, Italian, Finnish, Swedish and Norwegian (both Bokmål & Nynorsk).

If you'd like to provide translation help the instructions at the [translation wiki page](https://github.com/NatLibFi/Skosmos/wiki/Translation) will tell you how to do it.

### Contributing code

#### Development cycles and use of milestones
 Skosmos development is done in cycles lasting roughly two months. The planned work (issues) for the next/current cycle have a milestone indicating the version number of the planned release. The issues marked in the milestone next tasks are issues we haven't yet scheduled for a specific release but we'd like resolve in the near future. Issues marked blue sky are things we've decided to postpone for now. The milestones and open issues are evaluated after each release.
 
#### Making a commit
If you see an issue that you'd like to fix feel free to do so. If possible let us know you're working on an issue by leaving a comment on it so we'll be able to avoid doing the same work twice. This is especially useful if the issue has been marked for a release (in a milestone with a version number eg. "1.8") since it's more likely someone might be already working on it.

We do not strictly enforce a coding style but for PHP code following [PSR-1](http://www.php-fig.org/psr/psr-1/) is encouraged. We prefer to use 4 spaces for indenting.

If you've added new functionality or you've found out that the existing tests are lacking. We'd be happy if you could provide additional PHPUnit tests to cover it. Also see that your code passes all the existing tests by running PHPUnit. You can do this by running the test database initialization script init_fuseki.sh in the tests folder and then running vendor/bin/phpunit.

Please add a concise commit message for your contributions. In addition to describing the changes made include references to related issue numbers eg. "Description of the changes in the commit, related to #22". Then the GitHub issue tracker automatically links the related commits to the issue page. This makes debugging persistent bugs easier.

When you're happy with your finished contribution please send us a [pull request](https://help.github.com/articles/about-pull-requests/).
