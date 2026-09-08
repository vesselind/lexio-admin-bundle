// assets/src/controller_registrar.js
var controllerAttribute = "data-controller";
function registerControllers(application, eagerControllers, lazyControllers) {
  if (!application || typeof application.register !== "function") {
    throw new TypeError("A Stimulus application is required.");
  }
  const pendingControllers = new Map(Object.entries(lazyControllers));
  let observer = null;
  const isRegistered = (identifier) => application.router?.modulesByIdentifier?.has(identifier) ?? false;
  const register = (identifier, controller) => {
    if (!isRegistered(identifier)) {
      application.register(identifier, controller);
    }
  };
  const stopWatchingIfComplete = () => {
    if (pendingControllers.size === 0 && observer) {
      observer.disconnect();
      observer = null;
    }
  };
  const load = (identifier) => {
    const loader = pendingControllers.get(identifier);
    if (!loader) {
      return;
    }
    pendingControllers.delete(identifier);
    if (isRegistered(identifier)) {
      stopWatchingIfComplete();
      return;
    }
    Promise.resolve().then(loader).then((controller) => register(identifier, controller)).catch((error) => console.error(`Error loading controller "${identifier}":`, error)).finally(stopWatchingIfComplete);
  };
  const loadElement = (element) => {
    const identifiers = element?.getAttribute?.(controllerAttribute);
    if (identifiers) {
      identifiers.split(/\s+/).filter(Boolean).forEach(load);
    }
    element?.querySelectorAll?.(`[${controllerAttribute}]`).forEach(loadElement);
  };
  for (const [identifier, controller] of Object.entries(eagerControllers)) {
    register(identifier, controller);
  }
  if (pendingControllers.size > 0 && typeof document !== "undefined" && document.documentElement && typeof MutationObserver !== "undefined") {
    observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.type === "attributes") {
          loadElement(mutation.target);
          continue;
        }
        mutation.addedNodes?.forEach(loadElement);
      }
    });
    observer.observe(document.documentElement, {
      attributeFilter: [controllerAttribute],
      childList: true,
      subtree: true
    });
    loadElement(document.documentElement);
  }
  return {
    disconnect() {
      observer?.disconnect();
      observer = null;
      pendingControllers.clear();
    }
  };
}
export {
  registerControllers
};
