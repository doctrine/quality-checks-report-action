# Github Checker

A checker that parses static analysis tool output and performs the following
operations:

1. Create a check report with **all violations** and instructions how to fix
   them for contributors.

2. Inline comment failure reports to lines that are part of the changed commit
   range.

## Tools and APIs used

1. [exussum12/coverage-checker](https://github.com/exussum12/coverageChecker)
   with tooling to get a diff of all violations that match a commit range of a
   checked out repository.

2. [Github Checks](https://developer.github.com/v3/checks/)
