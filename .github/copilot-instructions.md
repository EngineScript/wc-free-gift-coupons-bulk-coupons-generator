---
applyTo: '**'
---
Coding standards, domain knowledge, and preferences that AI should follow.

# Work Environment

This project is coded entirely in a remote development environment using GitHub Codespaces. The AI will never ask me to run Terminal commands or use a local development environment. All code changes, tests, and debugging will be done within remote repositories on GitHub. 

Change summaries should be concise and clear, focusing on the specific changes made. The AI should not ask for confirmation before making changes, as all code modifications will be done directly in the remote environment. 

# Coding Standards and Preferences

## WordPress Focused Design

- This project is focused on WordPress development.
- Use WordPress coding standards and best practices.
- Leverage WordPress APIs and functions where applicable.
- Ensure compatibility with modern WordPress versions and PHP standards. WordPress 6.5+ and PHP 7.4+ are the baseline.
- Use WordPress hooks (actions and filters) to extend functionality.
- Follow WordPress theme and plugin development guidelines.
- Use WordPress REST API for custom endpoints and data retrieval.
- Ensure all code is compatible with the WordPress ecosystem, including themes and plugins.
- As this is a WordPress-focused project, avoid using frameworks or libraries that are not compatible with WordPress.
- Do not use frameworks or libraries that are not commonly used in the WordPress ecosystem.
- Avoid using non-standard or experimental features that are not widely adopted in the WordPress community.
- For any project that utilizes WooCommerce, ensure minimum version compatibility with WooCommerce 5.0+.

## WordPress Coding Standards

- Use WordPress coding standards for PHP, JavaScript, and CSS:
- [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- Use WordPress coding standards for HTML and template files:
- [HTML Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/html/)
- Use WordPress coding standards for accessibility:
- [Accessibility Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/)
- Use WordPress Gutenberg Project Coding Guidelines:
- [Gutenberg Project Coding Guidelines](https://developer.wordpress.org/block-editor/contributors/code/coding-guidelines/)
- Use WordPress JavaScript Documentation Standards:
- [JavaScript Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/javascript/)
- Use WordPress PHP Documentation Standards:
- [PHP Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/)

# General Coding Standards

- The above standards are prioritized over general coding standards.
- The standards below are general coding standards that apply to all code, including WordPress code. Do not apply them if they conflict with WordPress standards.

**Accessibility & UX**
- Follow accessibility best practices for UI components
- Ensure forms are keyboard-navigable and screen reader friendly
- Validate user-facing labels, tooltips, and messages for clarity

**Performance & Optimization**
- Optimize for performance and scalability where applicable
- Avoid premature optimization—focus on correctness first
- Detect and flag performance issues (e.g., unnecessary re-renders, N+1 queries)
- Use lazy loading, memoization, or caching where needed

**Type Safety & Standards**
- Use strict typing wherever possible (TypeScript, C#, etc.)
- Avoid using `any` or untyped variables
- Use inferred and narrow types when possible
- Define shared types centrally (e.g., `types/` or `shared/` folders)

**Security & Error Handling**
- Sanitize all input and output, especially in forms, APIs, and database interactions
- Escape, validate, and normalize all user-supplied data
- Automatically handle edge cases and error conditions
- Fail securely and log actionable errors
- Avoid leaking sensitive information in error messages or logs

**Code Quality & Architecture**
- Organize code using **feature-sliced architecture** when applicable
- Group code by **feature**, not by type (e.g., keep controller, actions, and helpers together by feature)
- Write clean, readable, and self-explanatory code
- Use meaningful and descriptive names for files, functions, and variables
- Remove unused imports, variables, and dead code automatically

**Task Execution & Automation**
- Always proceed to the next task automatically unless confirmation is required
- Only ask for confirmation when an action is destructive (e.g., data loss, deletion)
- Always attempt to identify and fix bugs automatically
- Only ask for manual intervention if domain-specific knowledge is required
- Auto-generate missing files, boilerplate, and tests when possible
- Auto-lint and format code using standard tools (e.g., Prettier, ESLint, dotnet format)