import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
    vus: 10,
    duration: "30s",
    thresholds: {
        http_req_failed: ["rate<0.01"],
        http_req_duration: ["p(95)<400"],
    },
};

const BASE_URL = __ENV.BASE_URL || "https://odds.lc";
const SPORT = __ENV.SPORT || "soccer_uefa_europa_conference_league";

export default function () {
    const res = http.get(`${BASE_URL}/api/lines?sport=${encodeURIComponent(SPORT)}`, {
        tags: { name: "lines" },
    });

    check(res, {
        "status 200": (r) => r.status === 200,
        "json-ish": (r) => (r.headers["Content-Type"] || "").includes("application/json"),
    });

    sleep(0.1);
}
