const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert');
const { test } = require('node:test');

const actionPath = path.join(process.cwd(), '.github/actions/publish-to-flight-log/action.yml');
const actionContent = fs.readFileSync(actionPath, 'utf8');

test('CreatePost mutation omits unsupported isSticky input', () => {
	assert.ok(
		!/isSticky:\s*\$isSticky/.test(actionContent),
		'GraphQL mutation should not pass isSticky'
	);
	assert.ok(
		!/\$isSticky:\s*Boolean/.test(actionContent),
		'GraphQL mutation should not declare isSticky variable'
	);
});

test('Sticky flag handled via REST fallback', () => {
	assert.ok(
		actionContent.includes('wp-json/wp/v2/posts/${postDatabaseId}'),
		'REST fallback for sticky posts is missing'
	);
	assert.ok(
		actionContent.includes('Post pinned (sticky).'),
		'Sticky success log should be present'
	);
});
