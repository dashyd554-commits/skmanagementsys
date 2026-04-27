function calculateSuccess(activity) {
    const participants = activity.participants || 0;
    const budget = activity.budget || 1;

    const score = participants / budget;

    return {
        title: activity.title,
        score: parseFloat(score.toFixed(2))
    };
}

function rankActivities(data) {
    return data
        .map(calculateSuccess)
        .sort((a, b) => b.score - a.score);
}