/**
 * Updates CHANGELOG.md with merged PR information
 * Categorises PRs based on conventional commit types
 * Used by the changelog-ci workflow
 *
 * Note: Exclusion checks (labels, commit types) are handled by check-changelog-exclusions.mjs
 * before this script is called, so we can assume the PR should be included.
 */

import { readFileSync, writeFileSync } from "fs";

/**
 * Maps conventional commit types to changelog sections
 */
const TYPE_TO_SECTION = {
	feat: "Added",
	fix: "Fixed",
	refactor: "Changed",
	perf: "Changed",
	revert: "Changed",
	remove: "Removed",
	security: "Security",
	change: "Changed",
	deprecate: "Deprecated",
};

/**
 * Maps commit types to custom display prefixes in changelog entries.
 * When a type is listed here, its capitalised name is used as the prefix
 * instead of the section name. Add new types here to override the default.
 */
const TYPE_TO_PREFIX = {
	revert: "Reverted",
	refactor: "Refactored",
};

/**
 * Words that would be redundant as the leading word of a changelog entry given
 * the section prefix already prefixes the entry.
 * Keyed by the prefix string resolved from TYPE_TO_PREFIX or TYPE_TO_SECTION.
 */
const PREFIX_REDUNDANT_WORDS = {
	Added: ["add", "adds", "added", "adding"],
	Fixed: ["fix", "fixes", "fixed", "fixing"],
	Changed: ["change", "changes", "changed", "changing"],
	Removed: ["remove", "removes", "removed", "removing"],
	Deprecated: ["deprecate", "deprecates", "deprecated", "deprecating"],
	Reverted: ["revert", "reverts", "reverted", "reverting"],
	Refactored: ["refactor", "refactors", "refactored", "refactoring"],
};

/**
 * Indentation used for PR description lines nested under a changelog list item — 2 spaces.
 */
const DESCRIPTION_INDENT = "  ";

/**
 * Array of included commit types derived from the keys of TYPE_TO_SECTION object
 */
export const INCLUDED_TYPES = Object.keys(TYPE_TO_SECTION);

/**
 * Build regex pattern to match conventional commit type prefix
 * Matches: type(scope)?: or type!: with optional whitespace after colon
 */
const COMMIT_TYPE_REGEX = new RegExp(
	`^(${INCLUDED_TYPES.join("|")})(\\(.+?\\))?!?:\\s*`,
	"i",
);

/**
 * Extracts the conventional commit type from a PR title
 * @param {string} title - PR title
 * @returns {string|null} - The type or null if not found
 */
function extractType(title) {
	const match = title.match(COMMIT_TYPE_REGEX);
	return match ? match[1].toLowerCase() : null;
}

/**
 * Strips the conventional commit type prefix from a PR title
 * @param {string} title - PR title
 * @returns {string} - Cleaned title
 */
function cleanTitle(title) {
	// Remove the type prefix (e.g., "feat: ", "fix(scope): ")
	const cleaned = title.replace(COMMIT_TYPE_REGEX, "");

	if (cleaned.length === 0) return title; // Fallback to original if something went wrong
	return cleaned;
}

/**
 * Gets or creates the Unreleased section in the changelog
 * @param {string} changelog - Current changelog content
 * @returns {object} - { hasUnreleased, lines, unreleasedIndex }
 */
function findOrCreateUnreleased(changelog) {
	const lines = changelog.split("\n");

	// Find if Unreleased section exists — handles both plain "## [Unreleased]"
	// and the URL-bearing variant "## [Unreleased](url)"
	const unreleasedIndex = lines.findIndex((line) =>
		line.match(/^## \[?Unreleased\]?/i),
	);

	if (unreleasedIndex !== -1) {
		return { hasUnreleased: true, lines, unreleasedIndex };
	}

	// Create Unreleased section — find first release section to insert before it
	let insertIndex = -1;

	// Look for the first release section (## [version])
	for (let i = 0; i < lines.length; i++) {
		if (lines[i].match(/^## \[.+\]/)) {
			insertIndex = i;
			break;
		}
	}

	// If no release found, append to the end of the document (fallback behaviour)
	if (insertIndex === -1) {
		insertIndex = lines.length;
	}

	// Insert Unreleased section
	const unreleasedSection = ["", "## [Unreleased]", ""];

	lines.splice(insertIndex, 0, ...unreleasedSection);

	return { hasUnreleased: false, lines, unreleasedIndex: insertIndex + 1 };
}

/**
 * Checks if a PR entry already exists in the Unreleased section
 * @param {array} lines - Changelog lines
 * @param {number} unreleasedIndex - Index of Unreleased header
 * @param {number} prNumber - PR number to check
 * @returns {boolean} - True if PR already exists
 */
function isDuplicateEntry(lines, unreleasedIndex, prNumber) {
	// Find the next version header (##) or end of file
	let nextSectionIndex = lines.length;
	for (let i = unreleasedIndex + 1; i < lines.length; i++) {
		if (lines[i].startsWith("## ")) {
			nextSectionIndex = i;
			break;
		}
	}

	// Check all lines in the Unreleased section for this PR number
	const prPattern = new RegExp(`\\[#${prNumber}\\]\\(`);
	for (let i = unreleasedIndex + 1; i < nextSectionIndex; i++) {
		if (prPattern.test(lines[i])) {
			return true;
		}
	}

	return false;
}

/**
 * Adds a PR entry to the appropriate section within Unreleased
 * @param {array} lines - Changelog lines
 * @param {number} unreleasedIndex - Index of Unreleased header
 * @param {string} section - Section name (Added, Fixed, etc.)
 * @param {string} entry - PR entry to add
 * @returns {array} - Updated lines
 */
function addEntryToSection(lines, unreleasedIndex, section, entry) {
	// Find the next version header (##) or end of file
	let nextSectionIndex = lines.length;
	for (let i = unreleasedIndex + 1; i < lines.length; i++) {
		if (lines[i].startsWith("## ")) {
			nextSectionIndex = i;
			break;
		}
	}

	// Look for the section header within Unreleased
	let sectionIndex = -1;
	for (let i = unreleasedIndex + 1; i < nextSectionIndex; i++) {
		if (lines[i].startsWith(`### ${section}`)) {
			sectionIndex = i;
			break;
		}
	}

	if (sectionIndex === -1) {
		// Section doesn't exist, create it
		let insertIndex = unreleasedIndex + 1;

		// Skip blank lines
		while (insertIndex < nextSectionIndex && lines[insertIndex].trim() === "") {
			insertIndex++;
		}

		// Skip all existing sections to add new section at the end
		while (
			insertIndex < nextSectionIndex &&
			lines[insertIndex].startsWith("### ")
		) {
			// Skip section header
			insertIndex++;

			// Skip all content until the next section header or end of Unreleased
			while (
				insertIndex < nextSectionIndex &&
				!lines[insertIndex].startsWith("### ")
			) {
				insertIndex++;
			}
		}

		// Insert new section
		lines.splice(insertIndex, 0, `### ${section}`, "", entry, "");
	} else {
		// Section exists, add entry to it
		let insertIndex = sectionIndex + 1;

		// Skip blank lines after section header
		while (insertIndex < nextSectionIndex && lines[insertIndex].trim() === "") {
			insertIndex++;
		}

		// Skip existing entries using <!-- end --> markers as definitive boundaries.
		// For entries without a marker (backward compatibility), stop at the next
		// entry title ("- ") or section header ("### ").
		while (
			insertIndex < nextSectionIndex &&
			lines[insertIndex].startsWith("- ")
		) {
			insertIndex++; // skip the entry title line
			// Advance past description lines/blank lines up to the <!-- end --> marker
			while (
				insertIndex < nextSectionIndex &&
				lines[insertIndex] !== "<!-- end -->" &&
				!lines[insertIndex].startsWith("- ") &&
				!lines[insertIndex].startsWith("### ")
			) {
				insertIndex++;
			}
			// Skip the <!-- end --> marker if present
			if (
				insertIndex < nextSectionIndex &&
				lines[insertIndex] === "<!-- end -->"
			) {
				insertIndex++;
			}
			// Skip any blank lines between entries
			while (insertIndex < nextSectionIndex && lines[insertIndex] === "") {
				insertIndex++;
			}
		}

		// Insert entry
		lines.splice(insertIndex, 0, entry);
	}

	return lines;
}

/**
 * Strips a redundant leading verb from the cleaned title when it duplicates
 * the prefix already applied to the changelog entry.
 * e.g. prefix="Added", cleanedTitle="add support for X" → "support for X"
 *      prefix="Fixed", cleanedTitle="fixed a bug"       → "a bug"
 * @param {string} prefix - Changelog entry prefix (e.g. "Added", "Fixed")
 * @param {string} cleanedTitle - PR title with commit-type prefix already stripped
 * @returns {string} - Title with the redundant leading word removed if applicable
 */
function stripRedundantLeadingWord(prefix, cleanedTitle) {
	const redundant = PREFIX_REDUNDANT_WORDS[prefix];
	if (!redundant) return cleanedTitle;

	const firstWordMatch = cleanedTitle.match(/^(\S+)(?:\s+(.*))?/i);
	if (!firstWordMatch) return cleanedTitle;

	const [, firstWord, rest] = firstWordMatch;
	if (redundant.includes(firstWord.toLowerCase())) {
		// If stripping the word leaves nothing meaningful, keep the original
		if (!rest?.trim()) return cleanedTitle;
		return rest;
	}

	return cleanedTitle;
}

/**
 * Formats the PR description with indentation for nesting under a list item
 * @param {string|null} prBody - PR description/body text
 * @returns {string} - Formatted description string (empty if no body)
 */
function formatPRDescription(prBody) {
	if (!prBody || prBody.trim() === "") {
		return "";
	}

	// Convert markdown headings to bold text
	const withoutHeadings = prBody.replace(/^#{1,6}\s+(.+)$/gm, "**$1**");

	// Indent each line with 2 spaces to nest under the list item.
	// Skip indentation on empty lines to avoid trailing whitespace.
	const indented = withoutHeadings
		.split("\n")
		.map((line) => (line ? `${DESCRIPTION_INDENT}${line}` : ""))
		.join("\n");

	// Always separate the description from the entry title with a blank line so
	// that markdown renders the description on its own line. Strip any leading
	// newlines from `indented` first to avoid double blank lines when prBody
	// itself starts with a blank line.
	return `\n\n${indented.replace(/^\n+/, "")}`;
}

/**
 * Builds the full changelog entry line for a PR
 * @param {string} type - Conventional commit type (e.g., "feat", "fix", "revert")
 * @param {string} section - Section name resolved from TYPE_TO_SECTION
 * @param {string} cleanedTitle - PR title with the type prefix stripped
 * @param {number} prNumber - PR number
 * @param {string} prUrl - PR HTML URL
 * @param {string} prAuthor - PR author login
 * @param {string|null} prBody - PR body/description
 * @returns {string} - Formatted entry line
 */
function buildEntry(
	type,
	section,
	cleanedTitle,
	prNumber,
	prUrl,
	prAuthor,
	prBody,
) {
	const prefix = TYPE_TO_PREFIX[type] ?? section;
	const dedupedTitle = stripRedundantLeadingWord(prefix, cleanedTitle);
	return `- ${prefix} ${dedupedTitle} ([#${prNumber}](${prUrl})) by @${prAuthor}${formatPRDescription(prBody)}\n<!-- end -->`;
}

/**
 * Main function to update the changelog
 */
export default async function updateChangelog({ pr, core }) {
	try {
		const prNumber = pr.number;
		const prTitle = pr.title;
		const prUrl = pr.html_url;
		const prAuthor = pr.user.login;
		const prBody = pr.body;

		console.log(`📝 Processing PR #${prNumber}: ${prTitle}`);

		// Extract type from PR title
		const type = extractType(prTitle);
		if (!type) {
			console.log(
				`⚠️  No valid conventional commit type found in PR title. Skipping changelog update.`,
			);
			return;
		}

		const section = TYPE_TO_SECTION[type];
		console.log(`📂 Type: ${type} → Section: ${section}`);

		// Read current changelog
		const changelogPath = "CHANGELOG.md";
		let changelog = "";
		try {
			changelog = readFileSync(changelogPath, "utf8");
		} catch (error) {
			console.log("CHANGELOG.md not found, creating new one");
			changelog = "# Changelog\n\n";
		}

		// Get or create Unreleased section
		const { lines, unreleasedIndex } = findOrCreateUnreleased(changelog);

		// Check if this PR is already in the changelog
		if (isDuplicateEntry(lines, unreleasedIndex, prNumber)) {
			console.log(
				`ℹ️  PR #${prNumber} already exists in the changelog. Skipping.`,
			);
			return;
		}

		// Format PR entry with cleaned title
		const cleanedTitle = cleanTitle(prTitle);
		const entry = buildEntry(
			type,
			section,
			cleanedTitle,
			prNumber,
			prUrl,
			prAuthor,
			prBody,
		);

		// Add entry to the appropriate section
		const updatedLines = addEntryToSection(
			lines,
			unreleasedIndex,
			section,
			entry,
		);

		// Write updated changelog
		const updatedChangelog = updatedLines.join("\n");
		writeFileSync(changelogPath, updatedChangelog);

		console.log(`✅ Updated CHANGELOG.md with PR #${prNumber}`);

		// Set outputs for the workflow to use
		core.setOutput("changelog-updated", "true");
		core.setOutput("pr-number", prNumber);
		core.setOutput("pr-title", cleanedTitle);
		core.setOutput("pr-author", prAuthor);
	} catch (error) {
		console.error("❌ Error updating changelog:", error);
		core.setFailed(`Failed to update changelog: ${error.message}`);
	}
}
