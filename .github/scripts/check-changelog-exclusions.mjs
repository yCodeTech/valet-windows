/**
 * Checks if a PR should be excluded from the changelog
 * Used by the changelog-ci workflow to determine early if processing should continue
 */

import { INCLUDED_TYPES } from "./update-changelog.mjs";

/**
 * Labels that should exclude PRs from the changelog
 */
const EXCLUDED_LABELS = [
	"skip-changelog",
	"no-changelog",
	"dependencies",
	"dependabot",
	"auto version bump",
	"release",
];

/**
 * Commit types that should exclude PRs from the changelog
 */
const EXCLUDED_TYPES = [
	"build",
	"chore",
	"ci",
	"docs",
	"image",
	"style",
	"test",
];

/**
 * All valid commit types (included + excluded)
 * Included types are derived from TYPE_TO_SECTION
 */
const ALL_COMMIT_TYPES = [...INCLUDED_TYPES, ...EXCLUDED_TYPES];

/**
 * Regex to match conventional commit type prefix in PR titles,
 * including both included and excluded types.
 */
const typeRegex = new RegExp(
	`^(${ALL_COMMIT_TYPES.join("|")})(\\(.+?\\))?!?:`,
	"i",
);

/**
 * Checks if the PR has any labels that are in the EXCLUDED_LABELS list.
 * The PR should be excluded from the changelog update process if any excluded label is found.
 *
 * @param {string[]} labels - Array of PR labels
 * @returns {boolean} - True if any label is excluded
 */
function hasExcludedLabel(labels) {
	return labels.some((label) =>
		EXCLUDED_LABELS.includes(label.name.toLowerCase()),
	);
}

/**
 * Gets the name of the excluded label, if any.
 * @param {string[]} labels - Array of PR labels
 * @returns {string|undefined} - The name of the excluded label, if any
 */
function getExcludedLabel(labels) {
	return labels.find((label) =>
		EXCLUDED_LABELS.includes(label.name.toLowerCase()),
	)?.name;
}

/**
 * Checks if a PR should be excluded from the changelog
 */
export default async function checkExclusions({ pr, core }) {
	try {
		const prTitle = pr.title;

		console.log(`🔍 Checking exclusions for PR #${pr.number}: ${prTitle}`);

		let shouldSkip = false;
		let skipReason = "";

		// Check for excluded labels
		if (hasExcludedLabel(pr.labels)) {
			const excludedLabel = getExcludedLabel(pr.labels);
			console.log(`⏭️  PR has excluded label "${excludedLabel}". Should skip.`);
			shouldSkip = true;
			skipReason = `excluded label: ${excludedLabel}`;
		}
		// Check for conventional commit type
		else {
			// Match the PR title against the regex to extract the commit type.
			const match = prTitle.match(typeRegex);

			// If no conventional commit type is found, skip the PR.
			if (!match) {
				console.log(
					"⚠️  No conventional commit type found in PR title. Should skip.",
				);
				shouldSkip = true;
				skipReason = "no conventional commit type";
			}
			// If a commit type is found, check if it's in the excluded types list.
			else {
				// Extract the commit type from the matched regex and
				// convert it to lowercase for comparison.
				const type = match[1].toLowerCase();

				// If the commit type is in the EXCLUDED_TYPES list, skip the PR.
				if (EXCLUDED_TYPES.includes(type)) {
					console.log(
						`⚠️  Conventional commit type "${type}" is excluded. Should skip.`,
					);
					shouldSkip = true;
					skipReason = `excluded type: ${type}`;
				}
				// If the commit type is NOT in the EXCLUDED_TYPES list, include the PR.
				else {
					console.log(`✅ PR will be included in changelog (type: ${type})`);
				}
			}
		}

		// Set outputs
		core.setOutput("should-skip", shouldSkip.toString());
		// If skipping, provide the reason.
		if (shouldSkip) {
			core.setOutput("skip-reason", skipReason);
		}
	} catch (error) {
		console.error("❌ Error checking exclusions:", error);
		core.setFailed(`Failed to check exclusions: ${error.message}`);
	}
}
