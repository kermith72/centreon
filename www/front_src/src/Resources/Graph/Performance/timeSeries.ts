import { map, pipe, reduce, filter, pathOr, addIndex } from 'ramda';

import { Metric, MetricData, GraphData } from './models';

interface TimeMetrics {
  time: number;
  metrics: Array<Metric>;
}

const toTimeMetrics = ({ metrics, times }): Array<TimeMetrics> => {
  return map(
    (time) => ({
      time,
      metrics,
    }),
    times,
  );
};

const toTimeWithMetrics = (
  { time, metrics }: TimeMetrics,
  timeIndex: number,
): MetricData => {
  const getMetricsForIndex = (): MetricData => {
    const addMetricForTimeIndex = (acc, { legend, data }): MetricData => ({
      ...acc,
      [legend]: data[timeIndex],
    });

    return reduce(addMetricForTimeIndex, {}, metrics);
  };

  return { time, ...getMetricsForIndex() };
};

const getTimeSeries = (graphData: GraphData): Array<MetricData> => {
  const isGreaterThanLowerLimit = (value): boolean =>
    value > pathOr(value - 1, ['global', 'lower-limit'], graphData);

  const rejectLowerThanLimit = ({
    time,
    ...metrics
  }: MetricData): MetricData => {
    return {
      ...filter(isGreaterThanLowerLimit, metrics),
      time,
    };
  };

  const indexedMap = addIndex<TimeMetrics, MetricData>(map);

  return pipe(
    toTimeMetrics,
    indexedMap(toTimeWithMetrics),
    map(rejectLowerThanLimit),
  )(graphData);
};

interface LegendColor {
  legend: string;
  color: string;
}

const toLegendColor = ({ ds_data, legend }: Metric): LegendColor => ({
  legend,
  color: ds_data.ds_color_line,
});

const getLegend = (graphData: GraphData): Array<LegendColor> => {
  return map(toLegendColor, graphData.metrics);
};

export default getTimeSeries;
export { getLegend };
